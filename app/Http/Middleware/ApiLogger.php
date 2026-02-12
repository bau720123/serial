<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 紀錄請求開始時間
        $requestAt = now();

        // 2. 執行 API 邏輯並取得回應
        $response = $next($request);

        // 3. 紀錄回應結束時間
        $responseAt = now();

        // 4. 寫入資料庫
        DB::connection('sqlsrv_serial')->table('serial_log')->insert([
            'api_name'    => $request->route()->getName() ?? '未定義 API',
            'host'        => $request->ip(),
            'api'         => $request->fullUrl(),
            'request'     => json_encode($request->all(), JSON_UNESCAPED_UNICODE),
            'request_at'  => $requestAt,
            'response'    => json_encode(json_decode($response->getContent(), true), JSON_UNESCAPED_UNICODE),
            'response_at' => $responseAt,
            'created_at'  => $requestAt,
        ]);

        return $response;
    }
}
