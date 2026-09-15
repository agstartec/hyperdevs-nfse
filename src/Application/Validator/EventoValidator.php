<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\Validator;

use Hyperdevs\Nfse\Application\DTO\Request\EventoRequest;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Domain\Enum\MotivoCancelamento;
use Hyperdevs\Nfse\Domain\Enum\MotivoRejeicao;
use Hyperdevs\Nfse\Domain\Enum\MotivoSubstituicao;
use Hyperdevs\Nfse\Domain\Enum\TipoEvento;

class EventoValidator
{
    public function validate(EventoRequest $request): void
    {
        $errors = [];
        $tipoEvento = null;

        if (empty($request->chaveNfse)) {
            $errors[] = 'Chave da NFSe é obrigatória';
        } elseif (preg_match('/^[0-9]{50}$/', $request->chaveNfse) !== 1) {
            $errors[] = 'Chave da NFSe deve ter exatamente 50 dígitos numéricos';
        }

        if (empty($request->tipoEvento)) {
            $errors[] = 'Tipo do evento é obrigatório';
        } else {
            try {
                $tipoEvento = TipoEvento::from($request->tipoEvento);
            } catch (\ValueError) {
                $errors[] = "Tipo de evento inválido: {$request->tipoEvento}";
                $tipoEvento = null;
            }
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'Versão da aplicação é obrigatória';
        } elseif (mb_strlen($request->versaoAplicacao) > 20) {
            $errors[] = 'Versão da aplicação deve ter no máximo 20 caracteres';
        }

        if ($request->cnpjAutor !== null && $request->cpfAutor !== null) {
            $errors[] = 'Informar apenas CNPJ Autor ou CPF Autor, não ambos';
        }

        if ($request->cnpjAutor !== null && preg_match('/^[0-9]{14}$/', $request->cnpjAutor) !== 1) {
            $errors[] = 'CNPJ Autor deve ter 14 dígitos numéricos';
        }

        if ($request->cpfAutor !== null && preg_match('/^[0-9]{11}$/', $request->cpfAutor) !== 1) {
            $errors[] = 'CPF Autor deve ter 11 dígitos numéricos';
        }

        if (isset($tipoEvento) && $tipoEvento->hasMotivo()) {
            if (empty($request->codigoMotivo)) {
                $errors[] = 'Código do motivo é obrigatório para este tipo de evento';
            } else {
                $motivoErrors = $this->validarCodigoMotivo($tipoEvento, $request->codigoMotivo);
                array_push($errors, ...$motivoErrors);
            }
        }

        if (isset($tipoEvento) && $tipoEvento->needsChSubstituta()) {
            if (empty($request->chSubstituta)) {
                $errors[] = 'Chave da NFS-e substituta (chSubstituta) é obrigatória para substituição';
            } elseif (preg_match('/^[0-9]{50}$/', $request->chSubstituta) !== 1) {
                $errors[] = 'chSubstituta deve ter 50 dígitos numéricos';
            }
        }

        if (isset($tipoEvento) && $tipoEvento->needsCpfAgTrib() && empty($request->cpfAgTrib)) {
            $errors[] = 'CPF do agente tributário (cpfAgTrib) é obrigatório para este tipo de evento';
        }

        if (isset($tipoEvento) && $tipoEvento->needsNumeroProcesso() && empty($request->nProcAdm)) {
            $errors[] = 'Número do processo administrativo (nProcAdm) é obrigatório para este tipo de evento';
        }

        // Campos minOccurs=1 específicos dos eventos de ofício/bloqueio/anulação (o builder os gera,
        // então a lib deve garanti-los antes do XML para não produzir documento inválido).
        if (isset($tipoEvento) && $tipoEvento->needsXProcAdm() && empty($request->xProcAdm)) {
            $errors[] = 'Descrição do processo administrativo (xProcAdm) é obrigatória para este tipo de evento';
        }

        if (isset($tipoEvento) && $tipoEvento->needsIdEvManifRej() && empty($request->idEvManifRej)) {
            $errors[] = 'Identificador do evento de manifestação rejeitado (idEvManifRej) é obrigatório para este tipo de evento';
        }

        if (isset($tipoEvento) && $tipoEvento->needsCodEventoBloqueio() && empty($request->codEventoBloqueio)) {
            $errors[] = 'Código do evento de bloqueio (codEvento) é obrigatório para este tipo de evento';
        }

        if (isset($tipoEvento) && $tipoEvento->needsIdBloqOfic() && empty($request->idBloqOfic)) {
            $errors[] = 'Identificador do bloqueio por ofício (idBloqOfic) é obrigatório para este tipo de evento';
        }

        // Validação de FORMATO dos campos de evento (patterns do XSD), aplicada sempre que o campo é informado.
        if (!empty($request->cpfAgTrib) && preg_match('/^[0-9]{11}$/', $request->cpfAgTrib) !== 1) {
            $errors[] = 'CPF do agente tributário (cpfAgTrib) deve ter 11 dígitos numéricos (TSCPF)';
        }

        if (!empty($request->nProcAdm) && preg_match('/^[0-9]{1,30}$/', $request->nProcAdm) !== 1) {
            $errors[] = 'Número do processo administrativo (nProcAdm) deve ter 1 a 30 dígitos numéricos (TSNumProcAdmAnaliseFiscalCanc)';
        }

        if (!empty($request->idEvManifRej) && preg_match('/^[0-9]{59}$/', $request->idEvManifRej) !== 1) {
            $errors[] = 'idEvManifRej deve ter 59 dígitos numéricos (TSIdNumEvento)';
        }

        if (!empty($request->idBloqOfic) && preg_match('/^[0-9]{59}$/', $request->idBloqOfic) !== 1) {
            $errors[] = 'idBloqOfic deve ter 59 dígitos numéricos (TSIdNumEvento)';
        }

        if (!empty($request->codEventoBloqueio)
            && !in_array($request->codEventoBloqueio, ['e101101', 'e105102', 'e105104', 'e105105', 'e305101'], true)
        ) {
            $errors[] = 'codEvento (codEventoBloqueio) inválido — deve ser e101101, e105102, e105104, e105105 ou e305101 (TSCodigoEventoNFSe)';
        }

        // xProcAdm é TSMotivo (15–255), igual ao xMotivo.
        if ($request->xProcAdm !== null && $request->xProcAdm !== '') {
            $tamanho = mb_strlen($request->xProcAdm);
            if ($tamanho < 15 || $tamanho > 255) {
                $errors[] = 'Descrição do processo administrativo (xProcAdm) deve ter entre 15 e 255 caracteres (TSMotivo)';
            }
        }

        // xMotivo é minOccurs=1 (obrigatório) no XSD para cancelamento, análise fiscal,
        // deferido, indeferido, anulação e ofícios. Onde é minOccurs=0 (substituição, rejeições).
        if (isset($tipoEvento) && $tipoEvento->descricaoMotivoObrigatoria() && empty($request->descricaoMotivo)) {
            $errors[] = 'Descrição do motivo (xMotivo) é obrigatória para este tipo de evento';
        }

        if (
            isset($tipoEvento)
            && $this->exigeDescricaoQuandoMotivoOutros($tipoEvento, $request->codigoMotivo)
            && empty($request->descricaoMotivo)
        ) {
            $errors[] = 'Descrição do motivo (xMotivo) é obrigatória quando o código do motivo é "Outros"';
        }

        if ($request->descricaoMotivo !== null && $request->descricaoMotivo !== '') {
            $tamanho = mb_strlen($request->descricaoMotivo);
            if ($tamanho < 15 || $tamanho > 255) {
                $errors[] = 'Descrição do motivo (xMotivo) deve ter entre 15 e 255 caracteres (TSMotivo)';
            }
        }

        if (empty($request->dataEvento)) {
            $errors[] = 'Data do evento é obrigatória';
        } elseif (\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $request->dataEvento) === false
            && \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $request->dataEvento) === false
            && \DateTimeImmutable::createFromFormat('Y-m-d', $request->dataEvento) === false
        ) {
            $errors[] = 'Data do evento inválida. Use ISO 8601 (ex: 2026-05-22T10:00:00-03:00)';
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }

    /** @return list<string> */
    private function validarCodigoMotivo(TipoEvento $tipoEvento, string $codigo): array
    {
        $errors = [];

        $validos = match ($tipoEvento) {
            TipoEvento::CANCELAMENTO => MotivoCancelamento::valores(),
            TipoEvento::SUBSTITUICAO => MotivoSubstituicao::valores(),
            TipoEvento::SOLICITACAO_ANALISE_FISCAL => MotivoCancelamento::valores(),
            TipoEvento::CANCELAMENTO_DEFERIDO => ['1'],
            TipoEvento::CANCELAMENTO_INDEFERIDO => ['1', '2'],
            TipoEvento::REJEICAO_PRESTADOR, TipoEvento::REJEICAO_TOMADOR, TipoEvento::REJEICAO_INTERMEDIARIO => MotivoRejeicao::valores(),
            default => [],
        };

        if (!empty($validos) && !in_array($codigo, $validos, true)) {
            $errors[] = "Código de motivo '{$codigo}' inválido para {$tipoEvento->value}. Valores permitidos: " . implode(', ', $validos);
        }

        return $errors;
    }

    private function exigeDescricaoQuandoMotivoOutros(TipoEvento $tipoEvento, ?string $codigoMotivo): bool
    {
        $ehRejeicao = in_array($tipoEvento, [
            TipoEvento::REJEICAO_PRESTADOR,
            TipoEvento::REJEICAO_TOMADOR,
            TipoEvento::REJEICAO_INTERMEDIARIO,
        ], true);

        return $ehRejeicao && $codigoMotivo === MotivoRejeicao::OUTROS->value;
    }
}
