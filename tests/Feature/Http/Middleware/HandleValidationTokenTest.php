<?php

use Actengage\Mailbox\Http\Middleware\HandleValidationToken;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('returns the validation token when present', function (): void {
    $request = Request::create('/test', 'POST', ['validationToken' => 'abc123']);
    $middleware = new HandleValidationToken;

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('next'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('abc123');
    expect($response->headers->get('Content-Type'))->toBe('text/plain');
});

it('passes through when no validation token is present', function (): void {
    $request = Request::create('/test', 'POST');
    $middleware = new HandleValidationToken;

    $response = $middleware->handle($request, fn (): ResponseFactory|Response => response('next'));

    expect($response->getContent())->toBe('next');
});
