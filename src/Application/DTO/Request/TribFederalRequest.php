<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class TribFederalRequest
{
    public function __construct(
        public ?string $pisCofinsCst = null,
        public ?string $pisCofinsTipo = null,
        public ?float $pisCofinsAliquotaPis = null,
        public ?float $pisCofinsAliquotaCofins = null,
        public ?string $valorRetidoCP = null,
        public ?string $valorRetidoIRRF = null,
        public ?string $valorRetidoCSLL = null,
        public ?string $pisCofinsBaseCalculo = null,
        public ?string $valorPis = null,
        public ?string $valorCofins = null,
    ) {
    }
}
