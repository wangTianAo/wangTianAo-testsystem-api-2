<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Facades\Test;
class TestServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('test', function(){
          return new Test;
        });
    }
}
