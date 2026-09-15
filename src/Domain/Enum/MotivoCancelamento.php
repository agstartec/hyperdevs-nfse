<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MotivoCancelamento: string
{
    case ERRO_EMISSAO = '1';
    case SERVICO_NAO_PRESTADO = '2';
    case OUTROS = '9';

    public function descricao(): string
    {
        return match ($this) {
            self::ERRO_EMISSAO => 'Erro na Emissão',
            self::SERVICO_NAO_PRESTADO => 'Serviço não Prestado',
            self::OUTROS => 'Outros',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
