<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class SubstituicaoRequest
{
    public function __construct(
        public string $chaveSubstituida,
        public string $codigoMotivo,
        public ?string $descricaoMotivo = null,
    ) {
    }
}
