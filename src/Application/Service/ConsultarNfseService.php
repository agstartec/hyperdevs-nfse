<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\Service;

use Hyperdevs\Nfse\Application\DTO\Request\ConsultaRequest;
use Hyperdevs\Nfse\Application\DTO\Response\NfseResponse;
use Hyperdevs\Nfse\Application\Exception\ServiceException;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Application\Validator\ConsultaValidator;
use Hyperdevs\Nfse\Config\ApiEndpoints;
use Hyperdevs\Nfse\Http\Contract\ApiConnectorInterface;
use Hyperdevs\Nfse\Http\Exception\HttpException;
use Hyperdevs\Nfse\Xml\Parser\NfseXmlParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ConsultarNfseService
{
    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private ApiEndpoints $apiEndpoints,
        private ConsultaValidator $validator,
        private NfseXmlParser $nfseXmlParser,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function consultarPorChave(string $chave, bool $encoding = false): ?NfseResponse
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarNfse($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return new NfseResponse(
                    success: false,
                    mensagem: 'NFSe não encontrada',
                );
            }

            $data = $response['data'];

            if (is_string($data) && str_contains($data, '<')) {
                $xml = $encoding ? mb_convert_encoding($data, 'ISO-8859-1') : $data;
                $parsed = $this->nfseXmlParser->parse($xml);
                return new NfseResponse(
                    success: true,
                    dados: $parsed[0] ?? null,
                    xml: $xml,
                );
            }

            return new NfseResponse(
                success: true,
                dados: is_array($data) ? $data : null,
            );

        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar NFSe: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao consultar NFSe: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function consultarDpsPorChave(string $chave): array
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarDps($chave);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return ['erro' => 'DPS não encontrada'];
            }

            $data = $response['data'];

            if (is_string($data)) {
                return json_decode($data, true) ?? ['dados' => $data];
            }

            return $data ?? [];

        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar DPS: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao consultar DPS: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function verificarDpsExiste(string $id): bool
    {
        // Id do DPS é TSIdDPS: "DPS" + 42 dígitos (45 caracteres). Valida antes de chamar a API.
        if (preg_match('/^DPS[0-9]{42}$/', $id) !== 1) {
            throw new ValidationException('Id do DPS deve estar no formato DPS seguido de 42 dígitos (TSIdDPS)');
        }

        try {
            $endpoint = $this->apiEndpoints->verificarDps($id);
            $response = $this->apiConnector->head($endpoint);

            return $response['success'];
        } catch (HttpException $e) {
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>|array<string, mixed>
     */
    public function consultarEventos(string $chave, ?string $tipoEvento = null, ?int $sequencial = null): array
    {
        try {
            $this->validator->validate(new ConsultaRequest($chave));

            $endpoint = $this->apiEndpoints->consultarEventos($chave, $tipoEvento, $sequencial);
            $response = $this->apiConnector->get($endpoint);

            if (!$response['success']) {
                return ['erro' => 'Eventos não encontrados'];
            }

            $data = $response['data'];
            return is_string($data) ? json_decode($data, true) ?? [] : $data ?? [];

        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao consultar eventos: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao consultar eventos: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

}
