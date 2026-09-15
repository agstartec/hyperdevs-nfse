<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

class BeneficioMunicipal
{
    public function __construct(
        private ?string $numeroBeneficio = null,
        private ?float $valorReducaoBC = null,
        private ?float $percentualReducaoBC = null,
    ) {
    }

    public function getNumeroBeneficio(): ?string
    {
        return $this->numeroBeneficio;
    }

    public function getValorReducaoBC(): ?float
    {
        return $this->valorReducaoBC;
    }

    public function getPercentualReducaoBC(): ?float
    {
        return $this->percentualReducaoBC;
    }
}
