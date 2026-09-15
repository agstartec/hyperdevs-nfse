<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Factory;

use Hyperevs\Nfse\Application\Service\CancelarNfseService;
use Hyperevs\Nfse\Application\Service\ConsultarNfseService;
use Hyperevs\Nfse\Application\Service\EmitirDpsService;
use Hyperevs\Nfse\Application\Validator\ConsultaValidator;
use Hyperevs\Nfse\Application\Validator\DpsValidator;
use Hyperevs\Nfse\Application\Validator\EventoValidator;
use Hyperevs\Nfse\Application\Validator\IbscbsResponseValidator;
use Hyperevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperevs\Nfse\Config\ApiEndpoints;
use Hyperevs\Nfse\Config\Config;
use Hyperevs\Nfse\Http\ApiConnector;
use Hyperevs\Nfse\Http\Client\CurlHttpClient;
use Hyperevs\Nfse\Http\RequestBuilder;
use Hyperevs\Nfse\Repository\CachedCstClassTribRepository;
use Hyperevs\Nfse\Repository\FileCstClassTribRepository;
use Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperevs\Nfse\Xml\Builder\DpsXmlBuilder;
use Hyperevs\Nfse\Xml\Builder\EventoXmlBuilder;
use Hyperevs\Nfse\Xml\Parser\NfseXmlParser;
use Hyperevs\Nfse\Xml\Validator\XsdValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServiceFactory
{
    private Config $configuration;
    private ApiConnector $apiConnector;
    private ApiEndpoints $apiEndpoints;
    private RequestBuilder $requestBuilder;
    private XsdValidator $xsdValidator;
    private LoggerInterface $logger;

    /**
     * @param Config|array<string, mixed> $config
     * @param CertificateManagerInterface $certificateManager implementação própria do integrador
     *        (ex.: um wrapper sem NFePHP), usada para materializar os PEMs consumidos pelo cURL.
     * @param XmlSignerInterface $xmlSigner implementação própria do integrador para assinatura XML.
     * @param LoggerInterface $logger logger injetado nos serviços. Padrão: NullLogger (sem saída).
     *        Para rastreabilidade em produção sem vazar dados sensíveis, passe um SanitizedLogger.
     */
    public function __construct(
        Config|array $config,
        private CertificateManagerInterface $certificateManager,
        private XmlSignerInterface $xmlSigner,
        LoggerInterface $logger = new NullLogger(),
    ) {
        $this->logger = $logger;
        $this->configuration = $config instanceof Config ? $config : new Config($config);
        $this->apiConnector = $this->createApiConnector();
        $this->apiEndpoints = new ApiEndpoints($this->configuration);
        $this->requestBuilder = new RequestBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    private function createApiConnector(): ApiConnector
    {
        // O cliente HTTP passa a possuir o CertificateManager: ele é o único
        // consumidor dos PEMs (lidos pelo cURL a cada request) e, possuindo o
        // manager, mantém os arquivos vivos enquanto existir. Posse e consumo
        // ficam no mesmo objeto, sem keep-alive externo dependente do GC.
        $httpClient = new CurlHttpClient(
            timeout: 60,
            connectTimeout: 10,
            certificateManager: $this->certificateManager,
            keyPassword: null,
        );

        return new ApiConnector(
            $this->configuration,
            $httpClient,
        );
    }

    public function createEmitirDpsService(): EmitirDpsService
    {
        return new EmitirDpsService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new DpsXmlBuilder(),
            xmlSigner: $this->xmlSigner,
            xsdValidator: $this->xsdValidator,
            validator: new DpsValidator($this->createCstClassTribRepository()),
            requestBuilder: $this->requestBuilder,
            nfseXmlParser: new NfseXmlParser(),
            ibscbsResponseValidator: new IbscbsResponseValidator(),
            apiEndpoints: $this->apiEndpoints,
            logger: $this->logger,
        );
    }

    /**
     * Repositório da tabela oficial CST x cClassTrib (regras de negócio do IBS/CBS da reforma
     * tributária). Sem ele, validateIbsCbsCstClassTrib do DpsValidator fica inativo.
     */
    private function createCstClassTribRepository(): CstClassTribRepository
    {
        $tabela = __DIR__ . '/../../Storage/cClassTrib.json';

        return new CachedCstClassTribRepository(
            new FileCstClassTribRepository($tabela),
        );
    }

    public function createConsultarNfseService(): ConsultarNfseService
    {
        return new ConsultarNfseService(
            apiConnector: $this->apiConnector,
            apiEndpoints: $this->apiEndpoints,
            validator: new ConsultaValidator(),
            nfseXmlParser: new NfseXmlParser(),
            logger: $this->logger,
        );
    }

    public function createCancelarNfseService(): CancelarNfseService
    {
        return new CancelarNfseService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new EventoXmlBuilder(),
            xmlSigner: $this->xmlSigner,
            xsdValidator: $this->xsdValidator,
            validator: new EventoValidator(),
            requestBuilder: $this->requestBuilder,
            apiEndpoints: $this->apiEndpoints,
            logger: $this->logger,
        );
    }
}
