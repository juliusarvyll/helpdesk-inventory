<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Event auto-discovery is disabled: it was double-registering every app/Listeners/*.php
    // class on top of the explicit Event::listen() calls in
    // App\Providers\ServiceDeskServiceProvider::boot(), causing each event to fire its
    // listeners (and therefore send each notification) twice. Listeners are registered
    // explicitly in ServiceDeskServiceProvider::boot() instead — see .ai/rules for the
    // implication for new listeners.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
