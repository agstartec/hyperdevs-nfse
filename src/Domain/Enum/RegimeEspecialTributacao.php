<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum RegimeEspecialTributacao: string
{
    case NENHUM = '0';
    case ATO_COOPERADO = '1';
    case ESTIMATIVA = '2';
    case MICROEMPRESA_MUNICIPAL = '3';
    case NOTARIO_REGISTRADOR = '4';
    case PROFISSIONAL_AUTONOMO = '5';
    case SOCIEDADE_PROFISSIONAIS = '6';
    case OUTROS = '9';

    public function descricao(): string
    {
        return match ($this) {
            self::NENHUM => 'Nenhum',
            self::ATO_COOPERADO => 'Ato Cooperado (Cooperativa)',
            self::ESTIMATIVA => 'Estimativa',
            self::MICROEMPRESA_MUNICIPAL => 'Microempresa Municipal',
            self::NOTARIO_REGISTRADOR => 'Notário ou Registrador',
            self::PROFISSIONAL_AUTONOMO => 'Profissional Autônomo',
            self::SOCIEDADE_PROFISSIONAIS => 'Sociedade de Profissionais',
            self::OUTROS => 'Outros',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
