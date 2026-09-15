<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoEvento: string
{
    case CANCELAMENTO = '101101';
    case SUBSTITUICAO = '105102';
    case SOLICITACAO_ANALISE_FISCAL = '101103';
    case CANCELAMENTO_DEFERIDO = '105104';
    case CANCELAMENTO_INDEFERIDO = '105105';
    case CONFIRMACAO_PRESTADOR = '202201';
    case CONFIRMACAO_TOMADOR = '203202';
    case CONFIRMACAO_INTERMEDIARIO = '204203';
    case CONFIRMACAO_TACITA = '205204';
    case REJEICAO_PRESTADOR = '202205';
    case REJEICAO_TOMADOR = '203206';
    case REJEICAO_INTERMEDIARIO = '204207';
    case ANULACAO_REJEICAO = '205208';
    case CANCELAMENTO_OFICIO = '305101';
    case BLOQUEIO_OFICIO = '305102';
    case DESBLOQUEIO_OFICIO = '305103';

    public function descricao(): string
    {
        return match ($this) {
            self::CANCELAMENTO => 'Cancelamento',
            self::SUBSTITUICAO => 'Substituição',
            self::SOLICITACAO_ANALISE_FISCAL => 'Solicitação de Análise Fiscal',
            self::CANCELAMENTO_DEFERIDO => 'Cancelamento Deferido',
            self::CANCELAMENTO_INDEFERIDO => 'Cancelamento Indeferido',
            self::CONFIRMACAO_PRESTADOR => 'Confirmação do Prestador',
            self::CONFIRMACAO_TOMADOR => 'Confirmação do Tomador',
            self::CONFIRMACAO_INTERMEDIARIO => 'Confirmação do Intermediário',
            self::CONFIRMACAO_TACITA => 'Confirmação Tácita',
            self::REJEICAO_PRESTADOR => 'Rejeição do Prestador',
            self::REJEICAO_TOMADOR => 'Rejeição do Tomador',
            self::REJEICAO_INTERMEDIARIO => 'Rejeição do Intermediário',
            self::ANULACAO_REJEICAO => 'Anulação da Rejeição',
            self::CANCELAMENTO_OFICIO => 'Cancelamento por Ofício',
            self::BLOQUEIO_OFICIO => 'Bloqueio por Ofício',
            self::DESBLOQUEIO_OFICIO => 'Desbloqueio por Ofício',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public function xDesc(): string
    {
        return match ($this) {
            self::CANCELAMENTO => 'Cancelamento de NFS-e',
            self::SUBSTITUICAO => 'Cancelamento de NFS-e por Substituição',
            self::SOLICITACAO_ANALISE_FISCAL => 'Solicitação de Análise Fiscal para Cancelamento de NFS-e',
            self::CANCELAMENTO_DEFERIDO => 'Cancelamento de NFS-e Deferido por Análise Fiscal',
            self::CANCELAMENTO_INDEFERIDO => 'Cancelamento de NFS-e Indeferido por Análise Fiscal',
            self::CONFIRMACAO_PRESTADOR => 'Manifestação de NFS-e - Confirmação do Prestador',
            self::CONFIRMACAO_TOMADOR => 'Manifestação de NFS-e - Confirmação do Tomador',
            self::CONFIRMACAO_INTERMEDIARIO => 'Manifestação de NFS-e - Confirmação do Intermediário',
            self::CONFIRMACAO_TACITA => 'Manifestação de NFS-e - Confirmação Tácita',
            self::REJEICAO_PRESTADOR => 'Manifestação de NFS-e - Rejeição do Prestador',
            self::REJEICAO_TOMADOR => 'Manifestação de NFS-e - Rejeição do Tomador',
            self::REJEICAO_INTERMEDIARIO => 'Manifestação de NFS-e - Rejeição do Intermediário',
            self::ANULACAO_REJEICAO => 'Manifestação de NFS-e - Anulação da Rejeição',
            self::CANCELAMENTO_OFICIO => 'Cancelamento de NFS-e por Ofício',
            self::BLOQUEIO_OFICIO => 'Bloqueio de NFS-e por Ofício',
            self::DESBLOQUEIO_OFICIO => 'Desbloqueio de NFS-e por Ofício',
        };
    }

    public function eventTypeTag(): string
    {
        return 'e' . $this->value;
    }

    public function needsChSubstituta(): bool
    {
        return $this === self::SUBSTITUICAO;
    }

    public function needsCpfAgTrib(): bool
    {
        return in_array($this, [
            self::CANCELAMENTO_DEFERIDO,
            self::CANCELAMENTO_INDEFERIDO,
            self::ANULACAO_REJEICAO,
            self::CANCELAMENTO_OFICIO,
            self::BLOQUEIO_OFICIO,
            self::DESBLOQUEIO_OFICIO,
        ], true);
    }

    public function needsNumeroProcesso(): bool
    {
        // nProcAdm é minOccurs=1 só no cancelamento por ofício (TE305101).
        // Em deferido/indeferido (TE105104/105105) é minOccurs=0 no XSD.
        return $this === self::CANCELAMENTO_OFICIO;
    }

    /** xProcAdm é minOccurs=1 no cancelamento por ofício (TE305101). */
    public function needsXProcAdm(): bool
    {
        return $this === self::CANCELAMENTO_OFICIO;
    }

    /** idEvManifRej é minOccurs=1 na anulação da rejeição (TE205208). */
    public function needsIdEvManifRej(): bool
    {
        return $this === self::ANULACAO_REJEICAO;
    }

    /** codEvento (codEventoBloqueio) é minOccurs=1 no bloqueio por ofício (TE305102). */
    public function needsCodEventoBloqueio(): bool
    {
        return $this === self::BLOQUEIO_OFICIO;
    }

    /** idBloqOfic é minOccurs=1 no desbloqueio por ofício (TE305103). */
    public function needsIdBloqOfic(): bool
    {
        return $this === self::DESBLOQUEIO_OFICIO;
    }

    public function hasMotivo(): bool
    {
        return in_array($this, [
            self::CANCELAMENTO,
            self::SUBSTITUICAO,
            self::SOLICITACAO_ANALISE_FISCAL,
            self::CANCELAMENTO_DEFERIDO,
            self::CANCELAMENTO_INDEFERIDO,
            self::REJEICAO_PRESTADOR,
            self::REJEICAO_TOMADOR,
            self::REJEICAO_INTERMEDIARIO,
        ], true);
    }

    /**
     * Indica se a descrição do motivo (xMotivo, mapeado de descricaoMotivo no builder) é obrigatória,
     * conforme o XSD (xMotivo minOccurs=1). Cobre cancelamento, análise fiscal, deferido, indeferido,
     * anulação da rejeição e bloqueio por ofício. Onde é minOccurs=0 (substituição, rejeições), a
     * obrigatoriedade dependeria de regra de NT — não validada aqui (política: seguir só o XSD).
     */
    public function descricaoMotivoObrigatoria(): bool
    {
        return in_array($this, [
            self::CANCELAMENTO,
            self::SOLICITACAO_ANALISE_FISCAL,
            self::CANCELAMENTO_DEFERIDO,
            self::CANCELAMENTO_INDEFERIDO,
            self::ANULACAO_REJEICAO,
            self::BLOQUEIO_OFICIO,
        ], true);
    }
}
