<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Enum;

enum TipoReembolsoRepasseRessarcimento: string
{
    case REPASSE_IMOVEIS_CORRETORES = '01';
    case REPASSE_FORNECEDOR_TURISMO = '02';
    case REEMBOLSO_PUBLICIDADE_PROD_EXTERNA = '03';
    case REEMBOLSO_PUBLICIDADE_MIDIA = '04';
    case OUTROS = '99';

    public function descricao(): string
    {
        return match ($this) {
            self::REPASSE_IMOVEIS_CORRETORES => 'Repasse de valores a corretores de imóveis',
            self::REPASSE_FORNECEDOR_TURISMO => 'Repasse de valores a fornecedores de serviços de turismo',
            self::REEMBOLSO_PUBLICIDADE_PROD_EXTERNA => 'Reembolso de despesas com publicidade e propaganda realizadas por produtor externo',
            self::REEMBOLSO_PUBLICIDADE_MIDIA => 'Reembolso de despesas com publicidade e propaganda realizadas por veículo de mídia',
            self::OUTROS => 'Outros',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
