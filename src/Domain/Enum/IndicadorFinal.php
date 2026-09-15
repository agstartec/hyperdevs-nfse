<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum IndicadorFinal: string
{
    case NAO = '0';
    case SIM = '1';

    public function descricao(): string
    {
        return match ($this) {
            self::NAO => 'Não',
            self::SIM => 'Sim (uso ou consumo pessoal)',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
