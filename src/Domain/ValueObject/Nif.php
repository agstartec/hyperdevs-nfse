<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\ValueObject;

use Hyperdevs\Nfse\Domain\Exception\ValidationException;

final readonly class Nif
{
    private string $nif;

    public function __construct(string $nif)
    {
        $this->nif = $this->validate($nif);
    }

    private function validate(string $nif): string
    {
        $nif = trim($nif);

        if (empty($nif)) {
            throw new ValidationException('NIF não pode ser vazio');
        }

        if (strlen($nif) > 40) {
            throw new ValidationException('NIF deve ter no máximo 40 caracteres');
        }

        return $nif;
    }

    public function getNif(): string
    {
        return $this->nif;
    }

    public function __toString(): string
    {
        return $this->nif;
    }
}
