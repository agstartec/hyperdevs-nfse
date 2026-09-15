<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum MovimentacaoTemporaria: string
{
    case DESCONHECIDO = '0';
    case NAO = '1';
    case VINCULADA_DI = '2';
    case VINCULADA_DE = '3';

    public function descricao(): string
    {
        return match ($this) {
            self::DESCONHECIDO => 'Desconhecido (tipo não informado na nota de origem)',
            self::NAO => 'Não',
            self::VINCULADA_DI => 'Vinculada - Declaração de Importação',
            self::VINCULADA_DE => 'Vinculada - Declaração de Exportação',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
