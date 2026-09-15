<?php

namespace Hyperevs\Nfse\Provider;

use Illuminate\Support\ServiceProvider;

class NfseProvider extends ServiceProvider
{
    /**
     * Registra serviços no container.
     */
    public function register(): void
    {
        // Aqui você vai registrar suas classes, configs, etc.
    }

    /**
     * Inicializa serviços (rotas, views, publicações).
     */
    public function boot(): void
    {
        // Aqui você vai carregar rotas, migrations, etc.
    }
}