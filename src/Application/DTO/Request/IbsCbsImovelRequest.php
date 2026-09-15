<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsImovelRequest
{
    public function __construct(
        public ?string $inscImobFisc = null,
        public ?string $cCIB = null,
        public ?IbsCbsEnderecoObraRequest $endereco = null,
    ) {
    }
}
