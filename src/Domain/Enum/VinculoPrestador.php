<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum VinculoPrestador: string
{
    case SEM_VINCULO = '0';
    case CONTROLADA = '1';
    case CONTROLADORA = '2';
    case COLIGADA = '3';
    case MATRIZ = '4';
    case FILIAL = '5';
    case OUTRO_VINCULO = '6';
    case DESCONHECIDO = '9';

    public function descricao(): string
    {
        return match ($this) {
            self::SEM_VINCULO => 'Sem vínculo com o Tomador/Prestador',
            self::CONTROLADA => 'Controlada',
            self::CONTROLADORA => 'Controladora',
            self::COLIGADA => 'Coligada',
            self::MATRIZ => 'Matriz',
            self::FILIAL => 'Filial ou sucursal',
            self::OUTRO_VINCULO => 'Outro vínculo',
            self::DESCONHECIDO => 'Desconhecido (tipo não informado na nota de origem)',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
