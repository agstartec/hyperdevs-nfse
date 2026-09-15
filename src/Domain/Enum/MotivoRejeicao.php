<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MotivoRejeicao: string
{
    case DUPLICIDADE = '1';
    case EMITIDA_TOMADOR = '2';
    case NAO_OCORRENCIA_FATO = '3';
    case ERRO_RESPONSABILIDADE = '4';
    case ERRO_VALOR = '5';
    case OUTROS = '9';

    public function descricao(): string
    {
        return match ($this) {
            self::DUPLICIDADE => 'NFS-e em duplicidade',
            self::EMITIDA_TOMADOR => 'NFS-e já emitida pelo tomador',
            self::NAO_OCORRENCIA_FATO => 'Não ocorrência do fato gerador',
            self::ERRO_RESPONSABILIDADE => 'Erro quanto a responsabilidade tributária',
            self::ERRO_VALOR => 'Erro quanto ao valor do serviço, valor das deduções ou serviço prestado ou data do fato gerador',
            self::OUTROS => 'Outros',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
