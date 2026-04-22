<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Agentis\AgentisAgent;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::get('/agentis-test', function () {
//     $agent    = app(AgentisAgent::class);
//     $response = $agent->prompt(
//         'How many users signed up this month?',
//         provider: config('agentis.provider')  // 'gemini'
//     );

//     return response()->json($response);
// });


Route::get('/agentis-test', function () {
    try {
        $start    = microtime(true);
        $agent    = app(AgentisAgent::class);

        $response = $agent->prompt(
            'Give me Matilde Ferry product details and let me know which user is associated with this particular product.',
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
