<?php

namespace Hyperdevs\Nfse\Provider;

use Hyperdevs\Nfse\Facade\NfseNacionalFacade;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NfseProvider extends ServiceProvider
{
    /**
     * Registra serviços no container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/nfse.php', 'nfse');

        $this->app->singleton(NfseManager::class, function ($app) {
            return new NfseManager(
                $app['config'],
                $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : new NullLogger(),
            );
        });

        // Atalho para quem emite nota para uma única empresa/certificado (config via .env).
        // Sistemas com vários clientes devem resolver NfseManager e chamar make() por request,
        // passando a prefeitura/certificado daquele cliente — veja o README.
        $this->app->bind(NfseNacionalFacade::class, function ($app) {
            return $app->make(NfseManager::class)->default();
        });
    }

    /**
     * Inicializa serviços (rotas, views, publicações).
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/nfse.php' => config_path('nfse.php'),
        ], 'nfse-config');
    }
}
