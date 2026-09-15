<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum VersaoSchema: string
{
    case V1_00 = '1.00';
    case V1_01 = '1.01';

    public function descricao(): string
    {
        return "v{$this->value}";
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
