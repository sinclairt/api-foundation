<?php

namespace Sinclair\ApiFoundation\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Fractal\FractalServiceProvider;

class ApiFoundationServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }

    public function register()
    {
        $this->app->register(FractalServiceProvider::class);
    }
}