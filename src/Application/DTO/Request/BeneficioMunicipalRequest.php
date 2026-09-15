<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class BeneficioMunicipalRequest
{
    public function __construct(
        public ?string $numeroBeneficio = null,
        public ?float $valorReducaoBC = null,
        public ?float $percentualReducaoBC = null,
    ) {
    }
}
