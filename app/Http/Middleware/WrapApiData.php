<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WrapApiData
{
  public function handle(Request $request, Closure $next) {
    $response = $next($request);
    if ($response instanceof JsonResponse) {
      $data = $response->getData();
      $statusCode = $response->getStatusCode();
      $success = $statusCode >= 200 && $statusCode < 300;
      $response->setData([
        'success' => $success,
        'data' => $data
      ]);
    }
    return $response;
  }
}