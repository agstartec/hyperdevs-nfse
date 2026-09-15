<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Facade;

use Hyperdevs\Nfse\Application\DTO\Request\DpsRequest;
use Hyperdevs\Nfse\Application\DTO\Request\EventoRequest;
use Hyperdevs\Nfse\Application\DTO\Response\EventoResponse;
use Hyperdevs\Nfse\Application\DTO\Response\NfseResponse;
use Hyperdevs\Nfse\Application\Exception\ServiceException;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Application\Service\CancelarNfseService;
use Hyperdevs\Nfse\Application\Service\ConsultarNfseService;
use Hyperdevs\Nfse\Application\Service\EmitirDpsService;
use Hyperdevs\Nfse\Config\Config;
use Hyperdevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperdevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperdevs\Nfse\Http\Security\Exception\CertificateExpiredException;
use Hyperdevs\Nfse\Factory\ServiceFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NfseNacionalFacade
{
    private EmitirDpsService $emitirDpsService;
    private ConsultarNfseService $consultarNfseService;
    private CancelarNfseService $cancelarNfseService;

    private function __construct(
        /** @var Config|array<string, mixed> */
        private Config|array $config,
        private CertificateManagerInterface $certificateManager,
        private XmlSignerInterface $xmlSigner,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->inicializarServicos();
    }

    /**
     * @param Config|array<string, mixed> $config aceita o array cru ou um objeto
     *        Config (ex.: produzido por ConfigFactory).
     * @param CertificateManagerInterface $certificateManager implementação própria do integrador
     *        (contrato definido em Http\Security\Contract; sem dependência do NFePHP nesta lib).
     * @param XmlSignerInterface $xmlSigner implementação própria do integrador para assinatura XML.
     * @param LoggerInterface $logger logger dos serviços. Padrão: NullLogger (sem saída). Para
     *        rastreabilidade em produção sem vazar dados sensíveis, passe um SanitizedLogger.
     * @throws CertificateExpiredException se o certificado estiver vencido. A proximidade de
     *         vencimento não é verificada pela lib — cabe ao integrador monitorá-la.
     */
    public static function create(
        Config|array $config,
        CertificateManagerInterface $certificateManager,
        XmlSignerInterface $xmlSigner,
        LoggerInterface $logger = new NullLogger(),
    ): self {
        return new self($config, $certificateManager, $xmlSigner, $logger);
    }

    /**
     * Emite uma NFS-e a partir de um DPS.
     *
     * @throws ValidationException se os dados do DPS forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }

    /**
     * Consulta uma NFS-e pela chave de acesso (50 dígitos).
     *
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave, $encoding);
    }

    /**
     * Consulta o DPS original de uma NFS-e pela chave de acesso.
     *
     * @return array<string, mixed>
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarDpsPorChave(string $chave): array
    {
        return $this->consultarNfseService->consultarDpsPorChave($chave);
    }

    /**
     * Cancela, manifesta ou substitui uma NFS-e via evento.
     *
     * @throws ValidationException se os dados do evento forem inválidos
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function cancelar(EventoRequest $request): EventoResponse
    {
        return $this->cancelarNfseService->executar($request);
    }

    /**
     * Consulta os eventos registrados para uma NFS-e.
     *
     * @return array<int, mixed>
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function consultarEventos(
        string $chave,
        ?string $tipoEvento = null,
        ?int $sequencial = null
    ): array {
        return array_values($this->consultarNfseService->consultarEventos(
            $chave,
            $tipoEvento,
            $sequencial
        ));
    }

    /**
     * Verifica se uma NFS-e já foi gerada a partir de um DPS (HEAD /dps/{id}).
     */
    public function verificarDpsExiste(string $id): bool
    {
        return $this->consultarNfseService->verificarDpsExiste($id);
    }

    /**
     * Emite uma NFS-e por decisão judicial (POST /decisao-judicial/nfse).
     *
     * @throws ValidationException se o XML da NFS-e for inválido
     * @throws ServiceException se a comunicação com a API falhar
     */
    public function emitirPorDecisaoJudicial(string $nfseXml): NfseResponse
    {
        return $this->emitirDpsService->executarPorDecisaoJudicial($nfseXml);
    }

    private function inicializarServicos(): void
    {
        $factory = new ServiceFactory($this->config, $this->certificateManager, $this->xmlSigner, $this->logger);

        $this->emitirDpsService = $factory->createEmitirDpsService();
        $this->consultarNfseService = $factory->createConsultarNfseService();
        $this->cancelarNfseService = $factory->createCancelarNfseService();
    }
}
