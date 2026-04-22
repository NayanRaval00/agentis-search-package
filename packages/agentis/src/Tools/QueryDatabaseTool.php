<?php

namespace Agentis\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class QueryDatabaseTool implements Tool
{
    public function __construct(
        protected array $tables  = [],
        protected int   $maxRows  = 100,
        protected int   $cacheTtl = 0,     // 0 = no cache
    ) {}

    public function description(): Stringable|string
    {
        $tableList = implode(', ', array_keys($this->tables));

        return "Query the application database with a safe SQL SELECT statement. "
            . "Available tables: {$tableList}. "
            . "Rules: SELECT only. Always LIMIT results. Use JOINs for related data. "
            . "Never use SELECT * — name specific columns.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema->string()
                ->description('A valid read-only SQL SELECT query with LIMIT.')
                ->required(),

            'explanation' => $schema->string()
                ->description('Plain English: what does this query fetch and why.')
                ->required(),
        ];
    }

    /**
     * Called by the AI agent framework.
     */
    public function handle(Request $request): Stringable|string
    {
        return json_encode(
            $this->executeSql(
                (string) $request->string('sql'),
                (string) $request->string('explanation'),
            )
        );
    }

    /**
     * Core logic — fully testable without Request.
     */
    public function executeSql(string $sql, string $explanation = ''): array
    {
        $sql = trim($sql);

        // ── Safety: SELECT only ──────────────────────────────────────────
        if (! preg_match('/^\s*SELECT\s/i', $sql)) {
            return ['error' => 'Only SELECT queries are permitted.'];
        }

        // ── Safety: block dangerous keywords ────────────────────────────
        $blocked = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'EXEC', 'GRANT'];
        foreach ($blocked as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return ['error' => "Keyword '{$keyword}' is not allowed."];
            }
        }

        // ── Safety: enforce LIMIT if missing ────────────────────────────
        if (! preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = rtrim($sql, '; ') . " LIMIT {$this->maxRows}";
        }

        // ── Performance: check cache ─────────────────────────────────────
        $cacheKey = 'agentis_query_' . md5($sql);

        if ($this->cacheTtl > 0 && Cache::has($cacheKey)) {
            $cached         = Cache::get($cacheKey);
            $cached['cached'] = true;
            return $cached;
        }

        // ── Execute ──────────────────────────────────────────────────────
        try {
            $startTime = microtime(true);
            $rows      = DB::select($sql);
            $duration  = round((microtime(true) - $startTime) * 1000, 2); // ms

            $rows = array_map(fn($r) => (array) $r, $rows);

            $result = [
                'success'       => true,
                'count'         => count($rows),
                'rows'          => $rows,
                'sql'           => $sql,
                'explanation'   => $explanation,
                'duration_ms'   => $duration,
                'cached'        => false,
            ];

            // Store in cache if enabled
            if ($this->cacheTtl > 0) {
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'sql'   => $sql,
            ];
        }
    }
}
