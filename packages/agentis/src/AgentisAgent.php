<?php

namespace Agentis;

use Agentis\Tools\QueryDatabaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class AgentisAgent implements Agent, HasTools, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $tables        = config('agentis.tables', []);
        $relationships = config('agentis.relationships', []);
        $maxRows       = config('agentis.max_rows', 100);
        $schemaLines   = [];

        foreach ($tables as $table => $def) {
            $cols          = implode(', ', $def['searchable'] ?? []);
            $label         = $def['label'] ?? $table;
            $schemaLines[] = "- `{$table}` ({$label}): columns [{$cols}]";
        }

        $schema = empty($schemaLines)
            ? 'No tables configured.'
            : implode("\n", $schemaLines);

        $relationshipLines = empty($relationships)
            ? 'None defined.'
            : implode("\n", array_map(fn($r) => "- {$r}", $relationships));

        return <<<INSTRUCTIONS
        You are an intelligent database assistant for a Laravel application.

        RULES:
        - Always use the query_database tool to fetch data before answering.
        - Never guess or fabricate data.
        - Only query the tables and columns listed below.
        - Always add LIMIT {$maxRows} to every query unless the user asks for a specific count.
        - Use JOIN when data from multiple tables is needed — prefer a single JOIN query over multiple queries.
        - Always use indexes: prefer filtering by id, sku, email over full-text columns.
        - Never use SELECT * — always name the specific columns you need.

        Available tables:
        {$schema}

        Table relationships (use these for JOINs):
        {$relationshipLines}
        INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new QueryDatabaseTool(
                tables: config('agentis.tables', []),
                maxRows: config('agentis.max_rows', 100),
                cacheTtl: config('agentis.cache_ttl', 0),
            ),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'answer'      => $schema->string()->description('Plain English answer.')->required(),
            'sql'         => $schema->string()->description('The SQL query executed.')->nullable(),
            'count'       => $schema->integer()->description('Number of rows returned.')->nullable(),
            'explanation' => $schema->string()->description('What the query did.')->nullable(),
        ];
    }
}
