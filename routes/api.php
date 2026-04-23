<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Agentis\AgentisAgent;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/agentis-test', function () {
    try {
        $start    = microtime(true);
        $agent    = app(AgentisAgent::class);

        $response = $agent->prompt(
            'Give "Holly Baumbach MD" and her profile details.',
            provider: config('agentis.provider')
        );

        $totalMs = round((microtime(true) - $start) * 1000);

        return response()->json([
            'success'       => true,
            'answer'        => $response['answer'],
            'sql'           => $response['sql'],
            'count'         => $response['count'],
            'explanation'   => $response['explanation'],
            'total_time_ms' => $totalMs,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
});
