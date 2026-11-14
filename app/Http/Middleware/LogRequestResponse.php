<?php

namespace App\Http\Middleware;

use App\Support\RequestResponseLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequestResponse
{
    public function __construct(private RequestResponseLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(RequestResponseLogger::STARTED_AT_ATTRIBUTE, microtime(true));

        $response = $next($request);

        $this->logger->record($request, $response);

        return $response;
    }
}
