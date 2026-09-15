<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

use Hyperevs\Nfse\Domain\Exception\ValidationException;

final readonly class Cep
{
    private string $cep;

    public function __construct(string $cep)
    {
        $this->cep = $this->validate($cep);
    }

    private function validate(string $cep): string
    {
        $cep = preg_replace('/[^0-9]/', '', $cep) ?? '';

        if (strlen($cep) !== 8) {
            throw new ValidationException(
                "CEP deve ter 8 dígitos. Fornecido: {$cep}"
            );
        }

        return $cep;
    }

    public function getCep(): string
    {
        return $this->cep;
    }

    public function formatado(): string
    {
        return sprintf(
            '%s.%s-%s',
            substr($this->cep, 0, 2),
            substr($this->cep, 2, 3),
            substr($this->cep, 5, 3)
        );
    }

    public function __toString(): string
    {
        return $this->cep;
    }
}
