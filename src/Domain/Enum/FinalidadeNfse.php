<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum FinalidadeNfse: string
{
    case REGULAR = '0';

    public function descricao(): string
    {
        return match ($this) {
            self::REGULAR => 'NFS-e regular',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
