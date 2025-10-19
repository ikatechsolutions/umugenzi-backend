<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php', // Ajoutez cette ligne pour les routes API
        // apiPrefix: 'api', // Cette ligne est facultative si vous voulez un préfixe différent de 'api'
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
