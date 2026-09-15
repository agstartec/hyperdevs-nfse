<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsEnderecoObraRequest
{
    public function __construct(
        public ?string $cep = null,
        public ?IbsCbsEnderecoExteriorRequest $endExt = null,
        public string $xLgr = '',
        public string $nro = '',
        public ?string $xCpl = null,
        public string $xBairro = '',
    ) {
    }
}
