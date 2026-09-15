<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\ValueObject;

use Hyperdevs\Nfse\Domain\Exception\ValidationException;

final readonly class CodigoMunicipio
{
    private string $codigo;

    public function __construct(string $codigo)
    {
        $this->codigo = $this->validate($codigo);
    }

    private function validate(string $codigo): string
    {
        $codigo = preg_replace('/[^0-9]/', '', $codigo) ?? '';

        if (strlen($codigo) !== 7) {
            throw new ValidationException(
                "Código do município deve ter 7 dígitos. Fornecido: {$codigo}"
            );
        }

        return $codigo;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function __toString(): string
    {
        return $this->codigo;
    }
}
