<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoEnteGovernamental: string
{
    case UNIAO = '1';
    case ESTADO = '2';
    case DISTRITO_FEDERAL = '3';
    case MUNICIPIO = '4';

    public function descricao(): string
    {
        return match ($this) {
            self::UNIAO => 'União',
            self::ESTADO => 'Estado',
            self::DISTRITO_FEDERAL => 'Distrito Federal',
            self::MUNICIPIO => 'Município',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
