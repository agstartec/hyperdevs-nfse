<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MecanismoApoioPrestador: string
{
    case DESCONHECIDO = '00';
    case NENHUM = '01';
    case ACC = '02';
    case ACE = '03';
    case BNDES_EXIM_POS = '04';
    case BNDES_EXIM_PRE = '05';
    case FGE = '06';
    case PROEX_EQUALIZACAO = '07';
    case PROEX_FINANCIAMENTO = '08';

    public function descricao(): string
    {
        return match ($this) {
            self::DESCONHECIDO => 'Desconhecido (tipo não informado na nota de origem)',
            self::NENHUM => 'Nenhum',
            self::ACC => 'ACC - Adiantamento sobre Contrato de Câmbio – Redução a Zero do IR e do IOF',
            self::ACE => 'ACE – Adiantamento sobre Cambiais Entregues - Redução a Zero do IR e do IOF',
            self::BNDES_EXIM_POS => 'BNDES-Exim Pós-Embarque – Serviços',
            self::BNDES_EXIM_PRE => 'BNDES-Exim Pré-Embarque - Serviços',
            self::FGE => 'FGE - Fundo de Garantia à Exportação',
            self::PROEX_EQUALIZACAO => 'PROEX - EQUALIZAÇÃO',
            self::PROEX_FINANCIAMENTO => 'PROEX - Financiamento',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
