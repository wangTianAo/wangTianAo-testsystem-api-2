<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


/**
**解决CORS问题的中间件
**在所有的响应头中放入Access-Control-Allow头部
**/
class CrossOrgin{
  public function handle($request, Closure $next){
    if($request->isMethod('options')){
      return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept');
    }

    $response = $next($request);

    return $response
          ->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Credentials', 'true')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept');
  }
}
