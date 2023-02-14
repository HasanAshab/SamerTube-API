<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
   public function handle($request, Closure $next, ...$guard){
    //check here if the user is authenticated
    if (!$this->auth->user()){
      abort(401);
    }
    return $next($request);
   }
}
