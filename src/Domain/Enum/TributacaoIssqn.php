<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TributacaoIssqn: string
{
    case OPERACAO_TRIBUTAVEL = '1';
    case IMUNIDADE = '2';
    case EXPORTACAO = '3';
    case NAO_INCIDENCIA = '4';

    public function descricao(): string
    {
        return match ($this) {
            self::OPERACAO_TRIBUTAVEL => 'Operação tributável',
            self::IMUNIDADE => 'Imunidade',
            self::EXPORTACAO => 'Exportação de serviço',
            self::NAO_INCIDENCIA => 'Não Incidência',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
