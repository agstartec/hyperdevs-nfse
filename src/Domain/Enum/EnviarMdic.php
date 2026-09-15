<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum EnviarMdic: string
{
    case NAO_ENVIAR = '0';
    case ENVIAR = '1';

    public function descricao(): string
    {
        return match ($this) {
            self::NAO_ENVIAR => 'Não enviar para o MDIC',
            self::ENVIAR => 'Enviar para o MDIC',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
