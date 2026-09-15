<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\ValidationException;

final readonly class CodigoClassificacaoTributaria
{
    private string $codigo;

    public function __construct(string $codigo)
    {
        $this->codigo = $this->validate($codigo);
    }

    private function validate(string $codigo): string
    {
        if (!preg_match('/^[0-9]{6}$/', $codigo)) {
            throw new ValidationException(
                "Código de Classificação Tributária deve ter exatamente 6 dígitos. Fornecido: {$codigo}"
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
