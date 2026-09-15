<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\InvalidChaveAcessoException;

final readonly class ChaveAcesso
{
    private string $chave;

    public function __construct(string $chave)
    {
        $this->chave = $this->validate($chave);
    }

    private function validate(string $chave): string
    {
        $chave = preg_replace('/[^0-9]/', '', $chave) ?? '';

        if (strlen($chave) !== 50) {
            throw new InvalidChaveAcessoException(
                'Chave de acesso deve ter 50 dígitos. Fornecido: ' . strlen($chave)
            );
        }

        return $chave;
    }

    public function getChave(): string
    {
        return $this->chave;
    }

    public function formatada(): string
    {
        return implode(' ', str_split($this->chave, 4));
    }

    public function __toString(): string
    {
        return $this->chave;
    }
}
