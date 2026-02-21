<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegisterIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if(auth()->check() && auth()->user()->canAccessPos()){

            if(!auth()->user()->openRegister()->exists()){
                return redirect()->route('register.open.form');
            }
        }

        return $next($request);
    }

}
