<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Factory;

use Hyperdevs\Nfse\Application\Service\CancelarNfseService;
use Hyperdevs\Nfse\Application\Service\ConsultarNfseService;
use Hyperdevs\Nfse\Application\Service\EmitirDpsService;
use Hyperdevs\Nfse\Application\Validator\ConsultaValidator;
use Hyperdevs\Nfse\Application\Validator\DpsValidator;
use Hyperdevs\Nfse\Application\Validator\EventoValidator;
use Hyperdevs\Nfse\Application\Validator\IbscbsResponseValidator;
use Hyperdevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperdevs\Nfse\Config\ApiEndpoints;
use Hyperdevs\Nfse\Config\Config;
use Hyperdevs\Nfse\Http\ApiConnector;
use Hyperdevs\Nfse\Http\Client\CurlHttpClient;
use Hyperdevs\Nfse\Http\RequestBuilder;
use Hyperdevs\Nfse\Repository\CachedCstClassTribRepository;
use Hyperdevs\Nfse\Repository\FileCstClassTribRepository;
use Hyperdevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperdevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperdevs\Nfse\Xml\Builder\DpsXmlBuilder;
use Hyperdevs\Nfse\Xml\Builder\EventoXmlBuilder;
use Hyperdevs\Nfse\Xml\Parser\NfseXmlParser;
use Hyperdevs\Nfse\Xml\Validator\XsdValidator;
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
