<?php

namespace Hyperevs\Nfse\Provider;

use Hyperevs\Nfse\Config\Config;
use Hyperevs\Nfse\Facade\NfseNacionalFacade;
use Hyperevs\Nfse\Http\Security\CertificateManager;
use Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperevs\Nfse\Http\Security\XmlSigner;
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

        // Implementação padrão (PFX + senha via .env), sem dependência do NFePHP.
        // Para usar outra fonte de certificado, sobrescreva o binding no ServiceProvider
        // da sua aplicação: $this->app->bind(CertificateManagerInterface::class, ...).
        $this->app->singleton(CertificateManagerInterface::class, function ($app) {
            $path = $app['config']['nfse.certificado.path'];
            $senha = (string) $app['config']['nfse.certificado.senha'];

            if (empty($path)) {
                throw new \RuntimeException(
                    'Configure NFSE_CERTIFICADO_PATH e NFSE_CERTIFICADO_SENHA no .env, ou vincule sua própria implementação de CertificateManagerInterface.'
                );
            }

            return CertificateManager::fromPfxFile($path, $senha);
        });

        $this->app->singleton(XmlSignerInterface::class, function ($app) {
            return new XmlSigner($app->make(CertificateManagerInterface::class)->getCertificate());
        });

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
