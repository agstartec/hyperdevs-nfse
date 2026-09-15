<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum RegimeTributario: int
{
    case REGIME_NORMAL = 1;
    case MEI = 2;
    case SIMPLES_NACIONAL = 3;

    public function descricao(): string
    {
        return match ($this) {
            self::REGIME_NORMAL => 'Não Optante',
            self::MEI => 'Optante - Microempreendedor Individual (MEI)',
            self::SIMPLES_NACIONAL => 'Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)',
        };
    }

    /** @return list<int> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
