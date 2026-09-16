<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Provider;

use Hyperdevs\Nfse\Config\Config;
use Hyperdevs\Nfse\Facade\NfseNacionalFacade;
use Hyperdevs\Nfse\Http\Security\CertificateManager;
use Hyperdevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperdevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperdevs\Nfse\Http\Security\XmlSigner;
use Illuminate\Contracts\Config\Repository;
use Psr\Log\LoggerInterface;

/**
 * Fábrica de NfseNacionalFacade. Existe porque prefeitura/certificado geralmente pertencem
 * à CONTA DO CLIENTE, não à aplicação — um valor fixo no .env só serve para quem emite nota
 * em nome de uma única empresa. Sistemas com vários clientes devem usar make() por request,
 * montando a config e o certificado a partir dos dados de cada cliente (ex.: vindos do banco).
 */
final class NfseManager
{
    private ?NfseNacionalFacade $defaultFacade = null;

    public function __construct(
        private readonly Repository $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Monta uma Facade para um cliente específico. Use este método quando cada cliente
     * do seu sistema tem sua própria prefeitura e/ou certificado digital.
     *
     * @param Config|array<string, mixed> $config ex.: ['tpAmb' => 2, 'prefeitura' => $cliente->codigo_ibge]
     */
    public function make(
        Config|array $config,
        CertificateManagerInterface $certificateManager,
        XmlSignerInterface $xmlSigner,
        ?LoggerInterface $logger = null,
    ): NfseNacionalFacade {
        return NfseNacionalFacade::create(
            config: $config,
            certificateManager: $certificateManager,
            xmlSigner: $xmlSigner,
            logger: $logger ?? $this->logger,
        );
    }

    /**
     * Facade única, montada a partir do .env (NFSE_PREFEITURA, NFSE_CERTIFICADO_*).
     * Use apenas quando a aplicação emite nota para uma única empresa/certificado.
     * Para múltiplos clientes, use make().
     */
    public function default(): NfseNacionalFacade
    {
        if ($this->defaultFacade !== null) {
            return $this->defaultFacade;
        }

        $path = $this->config->get('nfse.certificado.path');
        $senha = (string) $this->config->get('nfse.certificado.senha');
        $prefeitura = $this->config->get('nfse.prefeitura');

        if (empty($path) || empty($prefeitura)) {
            throw new \RuntimeException(
                'Nenhuma prefeitura/certificado padrão configurado no .env (NFSE_PREFEITURA, ' .
                'NFSE_CERTIFICADO_PATH, NFSE_CERTIFICADO_SENHA). Se o seu sistema atende vários ' .
                'clientes, cada um com prefeitura/certificado próprios, use NfseManager::make() ' .
                'informando os dados daquele cliente em vez de default().'
            );
        }

        $certificateManager = CertificateManager::fromPfxFile($path, $senha);

        return $this->defaultFacade = $this->make(
            config: new Config([
                'tpAmb' => $this->config->get('nfse.tp_amb'),
                'prefeitura' => $prefeitura,
                'tipoApi' => $this->config->get('nfse.tipo_api'),
            ]),
            certificateManager: $certificateManager,
            xmlSigner: new XmlSigner($certificateManager->getCertificate()),
        );
    }
}
