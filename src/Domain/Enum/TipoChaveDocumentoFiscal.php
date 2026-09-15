<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoChaveDocumentoFiscal: string
{
    case NFS_E = '1';
    case NF_E = '2';
    case CT_E = '3';
    case OUTRO = '9';

    public function descricao(): string
    {
        return match ($this) {
            self::NFS_E => 'Nota Fiscal de Serviço Eletrônica',
            self::NF_E => 'Nota Fiscal Eletrônica',
            self::CT_E => 'Conhecimento de Transporte Eletrônico',
            self::OUTRO => 'Outro',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
