<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MotivoSubstituicao: string
{
    case DESENQUADRAMENTO_SIMPLES = '01';
    case ENQUADRAMENTO_SIMPLES = '02';
    case INCLUSAO_IMUNIDADE = '03';
    case EXCLUSAO_IMUNIDADE = '04';
    case REJEICAO_TOMADOR = '05';
    case OUTROS = '99';

    public function descricao(): string
    {
        return match ($this) {
            self::DESENQUADRAMENTO_SIMPLES => 'Desenquadramento de NFS-e do Simples Nacional',
            self::ENQUADRAMENTO_SIMPLES => 'Enquadramento de NFS-e no Simples Nacional',
            self::INCLUSAO_IMUNIDADE => 'Inclusão Retroativa de Imunidade/Isenção para NFS-e',
            self::EXCLUSAO_IMUNIDADE => 'Exclusão Retroativa de Imunidade/Isenção para NFS-e',
            self::REJEICAO_TOMADOR => 'Rejeição de NFS-e pelo tomador ou pelo intermediário se responsável pelo recolhimento do tributo',
            self::OUTROS => 'Outros',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
