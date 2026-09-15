<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\InvalidCpfException;

final readonly class Cpf
{
    private string $numero;

    public function __construct(string $cpf)
    {
        $this->numero = $this->validate($cpf);
    }

    private function validate(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf) ?? '';

        if (strlen($cpf) !== 11) {
            throw new InvalidCpfException('CPF deve ter 11 dígitos. Fornecido: ' . strlen($cpf));
        }

        if (preg_match('/^(\d)\1+$/', $cpf)) {
            throw new InvalidCpfException('CPF inválido: sequência repetida');
        }

        if (!$this->validarDigitoVerificador($cpf)) {
            throw new InvalidCpfException('CPF com dígito verificador inválido');
        }

        return $cpf;
    }

    private function validarDigitoVerificador(string $cpf): bool
    {
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int)$cpf[$i] * (10 - $i);
        }

        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cpf[9] !== $digito1) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int)$cpf[$i] * (11 - $i);
        }

        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return (int)$cpf[10] === $digito2;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function formatado(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->numero, 0, 3),
            substr($this->numero, 3, 3),
            substr($this->numero, 6, 3),
            substr($this->numero, 9, 2)
        );
    }

    public function equals(self $other): bool
    {
        return $this->numero === $other->numero;
    }

    public function __toString(): string
    {
        return $this->numero;
    }
}
