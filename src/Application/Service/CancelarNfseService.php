<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\Service;

use Hyperdevs\Nfse\Application\DTO\Request\EventoRequest;
use Hyperdevs\Nfse\Application\DTO\Response\EventoResponse;
use Hyperdevs\Nfse\Application\Exception\ServiceException;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Application\Validator\EventoValidator;
use Hyperdevs\Nfse\Domain\Entity\Evento;
use Hyperdevs\Nfse\Domain\Enum\TipoEvento;
use Hyperdevs\Nfse\Domain\Exception\DomainException;
use Hyperdevs\Nfse\Domain\ValueObject\ChaveAcesso;
use Hyperdevs\Nfse\Config\ApiEndpoints;
use Hyperdevs\Nfse\Http\Contract\ApiConnectorInterface;
use Hyperdevs\Nfse\Http\Exception\HttpException;
use Hyperdevs\Nfse\Http\RequestBuilder;
use Hyperdevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperdevs\Nfse\Xml\Builder\Contract\XmlBuilderInterface;
use Hyperdevs\Nfse\Xml\Validator\Contract\XsdValidatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CancelarNfseService
{
    use SefinErrorMessageTrait;

    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private EventoValidator $validator,
        private RequestBuilder $requestBuilder,
        private ApiEndpoints $apiEndpoints,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function executar(EventoRequest $request): EventoResponse
    {
        try {
            $this->validator->validate($request);

            $evento = new Evento(
                tipo: TipoEvento::from($request->tipoEvento),
                chaveNfse: new ChaveAcesso($request->chaveNfse),
                dataEvento: new \DateTimeImmutable($request->dataEvento),
                versaoAplicacao: $request->versaoAplicacao,
                tipoAmbiente: $request->tipoAmbiente,
                cnpjAutor: $request->cnpjAutor,
                cpfAutor: $request->cpfAutor,
                codigoMotivo: $request->codigoMotivo,
                descricaoMotivo: $request->descricaoMotivo,
                nSeqEvento: $request->nSeqEvento,
                ambGer: $request->ambGer,
                dhProc: $request->dhProc !== null ? new \DateTimeImmutable($request->dhProc) : null,
                nDFSe: $request->nDFSe,
                chSubstituta: $request->chSubstituta,
                cpfAgTrib: $request->cpfAgTrib,
                nProcAdm: $request->nProcAdm,
                xProcAdm: $request->xProcAdm,
                idEvManifRej: $request->idEvManifRej,
                codEventoBloqueio: $request->codEventoBloqueio,
                idBloqOfic: $request->idBloqOfic,
            );

            $xml = $this->xmlBuilder->build($evento);

            $this->xsdValidator->validate($xml, 'pedRegEvento');

            $xmlAssinado = $this->xmlSigner->sign($xml, 'infPedReg', 'pedRegEvento');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $payload = $this->requestBuilder->buildEventoPayload($xmlAssinado);

            $endpoint = $this->apiEndpoints->cancelarNfse($request->chaveNfse);
            $response = $this->apiConnector->post($endpoint, $payload);

            if (!$response['success']) {
                return new EventoResponse(
                    success: false,
                    mensagem: $this->extrairMensagemErro($response['data'] ?? null, 'Erro ao cancelar NFSe'),
                    dados: $response['data'] ?? null,
                    erros: $this->extrairErros($response['data'] ?? null),
                );
            }

            return new EventoResponse(
                success: true,
                mensagem: 'Cancelamento realizado com sucesso',
                dados: $response['data'] ?? null,
            );

        } catch (DomainException $e) {
            $this->logger->warning('Validação cancelamento falhou: {msg}', ['msg' => $e->getMessage()]);
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao cancelar NFSe: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao cancelar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
