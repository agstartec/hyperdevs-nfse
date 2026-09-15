<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\ValidationException;

final readonly class CodigoSituacaoTributaria
{
    private string $codigo;

    public function __construct(string $codigo)
    {
        $this->codigo = $this->validate($codigo);
    }

    private function validate(string $codigo): string
    {
        if (!preg_match('/^[0-9]{3}$/', $codigo)) {
            throw new ValidationException(
                "Código de Situação Tributária deve ter exatamente 3 dígitos. Fornecido: {$codigo}"
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
