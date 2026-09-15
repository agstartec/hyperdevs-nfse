<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum CausaNaoNif: string
{
    case NAO_INFORMADO = '0';
    case DISPENSADO = '1';
    case NAO_EXIGENCIA = '2';

    public function descricao(): string
    {
        return match ($this) {
            self::NAO_INFORMADO => 'Não informado na nota de origem',
            self::DISPENSADO => 'Dispensado do NIF',
            self::NAO_EXIGENCIA => 'Não exigência do NIF',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
