<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum TipoAmbiente: int
{
    case PRODUCAO = 1;
    case HOMOLOGACAO = 2;

    public function descricao(): string
    {
        return match ($this) {
            self::PRODUCAO => 'Produção',
            self::HOMOLOGACAO => 'Homologação',
        };
    }

    public function isProducao(): bool
    {
        return $this === self::PRODUCAO;
    }

    /** @return list<int> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
