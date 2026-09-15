<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MotivoEmissaoTI: int
{
    case IMPORTACAO_SERVICO = 1;
    case OBRIGADO_LEGISLACAO_MUNICIPAL = 2;
    case RECUSA_EMISSAO_PRESTADOR = 3;
    case REJEICAO_NFSE_PRESTADOR = 4;

    public function descricao(): string
    {
        return match ($this) {
            self::IMPORTACAO_SERVICO => 'Importação de Serviço',
            self::OBRIGADO_LEGISLACAO_MUNICIPAL => 'Obrigado a emitir NFS-e por legislação municipal',
            self::RECUSA_EMISSAO_PRESTADOR => 'Emitindo NFS-e por recusa de emissão pelo prestador',
            self::REJEICAO_NFSE_PRESTADOR => 'Emitindo por rejeitar a NFS-e emitida pelo prestador',
        };
    }

    /** @return list<int> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
