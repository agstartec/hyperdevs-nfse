<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\ValidationException;

final readonly class CodigoCreditoPresumido
{
    private string $codigo;

    public function __construct(string $codigo)
    {
        $this->codigo = $this->validate($codigo);
    }

    private function validate(string $codigo): string
    {
        if (!preg_match('/^[0-9]{2}$/', $codigo)) {
            throw new ValidationException(
                "Código de Crédito Presumido deve ter exatamente 2 dígitos. Fornecido: {$codigo}"
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
