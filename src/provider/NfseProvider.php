<?php

namespace Hyperevs\Nfse\Provider;

use Hyperevs\Nfse\Config\Config;
use Hyperevs\Nfse\Facade\NfseNacionalFacade;
use Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperevs\Nfse\Http\Security\Contract\XmlSignerInterface;
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

        $this->app->singleton(Config::class, function ($app) {
            return new Config([
                'tpAmb' => $app['config']['nfse.tp_amb'],
                'prefeitura' => $app['config']['nfse.prefeitura'],
                'tipoApi' => $app['config']['nfse.tipo_api'],
            ]);
        });

        // CertificateManagerInterface e XmlSignerInterface não têm implementação própria
        // desta lib (propositalmente, sem depender do pacote nfephp-org/sped-common).
        // Vincule suas implementações no ServiceProvider da sua aplicação, ex.:
        //   $this->app->bind(CertificateManagerInterface::class, MeuCertificateManager::class);
        //   $this->app->bind(XmlSignerInterface::class, MeuXmlSigner::class);
        $this->app->singleton(NfseNacionalFacade::class, function ($app) {
            return NfseNacionalFacade::create(
                config: $app->make(Config::class),
                certificateManager: $app->make(CertificateManagerInterface::class),
                xmlSigner: $app->make(XmlSignerInterface::class),
                logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : new NullLogger(),
            );
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
