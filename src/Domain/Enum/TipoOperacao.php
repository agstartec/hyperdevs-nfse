<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum TipoOperacao: string
{
    case FORNECIMENTO_POSTERIOR = '1';
    case RECEBIMENTO_FORNECIMENTO_REALIZADO = '2';
    case FORNECIMENTO_PAGAMENTO_REALIZADO = '3';
    case RECEBIMENTO_FORNECIMENTO_POSTERIOR = '4';
    case CONCOMITANTE = '5';

    public function descricao(): string
    {
        return match ($this) {
            self::FORNECIMENTO_POSTERIOR => 'Fornecimento com pagamento posterior',
            self::RECEBIMENTO_FORNECIMENTO_REALIZADO => 'Recebimento do pagamento com fornecimento já realizado',
            self::FORNECIMENTO_PAGAMENTO_REALIZADO => 'Fornecimento com pagamento já realizado',
            self::RECEBIMENTO_FORNECIMENTO_POSTERIOR => 'Recebimento do pagamento com fornecimento posterior',
            self::CONCOMITANTE => 'Fornecimento e recebimento do pagamento concomitantes',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
