<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\ValidationException;

final readonly class Telefone
{
    private string $numero;

    public function __construct(string $numero)
    {
        $this->numero = $this->validate($numero);
    }

    private function validate(string $numero): string
    {
        $numero = preg_replace('/[^0-9]/', '', $numero) ?? '';

        $len = strlen($numero);

        // XSD TSTelefone: [0-9]{6,20} — suporta telefones nacionais e internacionais.
        if ($len < 6 || $len > 20) {
            throw new ValidationException(
                "Telefone deve ter entre 6 e 20 dígitos. Fornecido: {$numero}"
            );
        }

        return $numero;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function formatado(): string
    {
        $len = strlen($this->numero);

        if ($len === 11) {
            return sprintf('(%s) %s-%s', substr($this->numero, 0, 2), substr($this->numero, 2, 5), substr($this->numero, 7, 4));
        }

        if ($len === 10) {
            return sprintf('(%s) %s-%s', substr($this->numero, 0, 2), substr($this->numero, 2, 4), substr($this->numero, 6, 4));
        }

        return $this->numero;
    }

    public function __toString(): string
    {
        return $this->numero;
    }
}
