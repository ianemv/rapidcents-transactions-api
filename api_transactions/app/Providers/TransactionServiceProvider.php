<?php

namespace App\Providers;

use App\Services\TransactionService;
use Illuminate\Support\ServiceProvider;
use App\Services\CardService;

class TransactionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(TransactionService::class, function ($app) {
            return new TransactionService($app->make(CardService::class));
        });
    }
}
