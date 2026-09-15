<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class ObraRequest
{
    public function __construct(
        public ?string $inscImobFisc = null,
        public ?string $cObra = null,
        public ?string $cCIB = null,
        public ?IbsCbsEnderecoObraRequest $endereco = null,
    ) {
    }
}
