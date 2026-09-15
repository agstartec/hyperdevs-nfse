<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsEnderecoExteriorRequest
{
    public function __construct(
        public string $cEndPost,
        public string $xCidade,
        public string $xEstProvReg,
    ) {
    }
}
