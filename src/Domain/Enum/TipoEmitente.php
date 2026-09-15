<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoEmitente: int
{
    case PRESTADOR = 1;
    case TOMADOR = 2;
    case INTERMEDIARIO = 3;

    public function descricao(): string
    {
        return match ($this) {
            self::PRESTADOR => 'Emitente: Prestador',
            self::TOMADOR => 'Emitente: Tomador',
            self::INTERMEDIARIO => 'Emitente: Intermediário',
        };
    }

    /** @return list<int> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
