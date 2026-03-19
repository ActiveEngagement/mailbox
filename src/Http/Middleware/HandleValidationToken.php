<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleValidationToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('validationToken')) {
            return response($request->string('validationToken')->toString(), 200)->header('Content-Type', 'text/plain');
        }

        return $next($request);
    }
}
