<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoRetencaoIssqn: string
{
    case NAO_RETIDO = '1';
    case RETIDO_TOMADOR = '2';
    case RETIDO_INTERMEDIARIO = '3';

    public function descricao(): string
    {
        return match ($this) {
            self::NAO_RETIDO => 'Não Retido',
            self::RETIDO_TOMADOR => 'Retido pelo Tomador',
            self::RETIDO_INTERMEDIARIO => 'Retido pelo Intermediário',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
