<?php

use Actengage\Mailbox\Http\Middleware\HandleValidationToken;
use Illuminate\Http\Request;

it('returns the validation token when present', function (): void {
    $request = Request::create('/test', 'POST', ['validationToken' => 'abc123']);
    $middleware = new HandleValidationToken;

    $response = $middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('abc123');
    expect($response->headers->get('Content-Type'))->toBe('text/plain');
});

it('passes through when no validation token is present', function (): void {
    $request = Request::create('/test', 'POST');
    $middleware = new HandleValidationToken;

    $response = $middleware->handle($request, fn () => response('next'));

    expect($response->getContent())->toBe('next');
});
