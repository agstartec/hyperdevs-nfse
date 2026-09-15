<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum IndicadorDestinacao: string
{
    case TOMADOR = '0';
    case TERCEIRO = '1';

    public function descricao(): string
    {
        return match ($this) {
            self::TOMADOR => 'Destinatário é o próprio tomador',
            self::TERCEIRO => 'Destinatário é terceiro (diferente do tomador)',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
