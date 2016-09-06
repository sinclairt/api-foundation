<?php

namespace Sinclair\ApiFoundation\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Fractal\FractalServiceProvider;

class ApiFoundationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->register(FractalServiceProvider::class);
    }

    public function register()
    {

    }
}