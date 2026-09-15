<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\ValueObject;

use Hyperdevs\Nfse\Domain\Exception\InvalidCnpjException;

final readonly class Cnpj
{
    private string $numero;

    public function __construct(string $cnpj)
    {
        $this->numero = $this->validate($cnpj);
    }

    private function validate(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj) ?? '';

        if (strlen($cnpj) !== 14) {
            throw new InvalidCnpjException('CNPJ deve ter 14 dígitos. Fornecido: ' . strlen($cnpj));
        }

        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            throw new InvalidCnpjException('CNPJ inválido: sequência repetida');
        }

        if (!$this->validarDigitoVerificador($cnpj)) {
            throw new InvalidCnpjException('CNPJ com dígito verificador inválido');
        }

        return $cnpj;
    }

    private function validarDigitoVerificador(string $cnpj): bool
    {
        $soma = 0;
        $multiplicador = 5;

        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador === 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if ((int)$cnpj[12] !== $digito1) {
            return false;
        }

        $soma = 0;
        $multiplicador = 6;

        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador === 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        return (int)$cnpj[13] === $digito2;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function formatado(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->numero, 0, 2),
            substr($this->numero, 2, 3),
            substr($this->numero, 5, 3),
            substr($this->numero, 8, 4),
            substr($this->numero, 12, 2)
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
