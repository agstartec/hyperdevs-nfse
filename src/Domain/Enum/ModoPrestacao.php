<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum ModoPrestacao: string
{
    case DESCONHECIDO = '0';
    case TRANSFRONTEIRICO = '1';
    case CONSUMO_BRASIL = '2';
    case PRESENCA_COMERCIAL_EXTERIOR = '3';
    case MOVIMENTO_TEMPORARIO_PF = '4';

    public function descricao(): string
    {
        return match ($this) {
            self::DESCONHECIDO => 'Desconhecido (tipo não informado na nota de origem)',
            self::TRANSFRONTEIRICO => 'Transfronteiriço',
            self::CONSUMO_BRASIL => 'Consumo no Brasil',
            self::PRESENCA_COMERCIAL_EXTERIOR => 'Presença Comercial no Exterior',
            self::MOVIMENTO_TEMPORARIO_PF => 'Movimento Temporário de Pessoas Físicas',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
