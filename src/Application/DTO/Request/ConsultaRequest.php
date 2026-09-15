<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class ConsultaRequest
{
    public function __construct(
        public string $chave,
        public ?string $tipoEvento = null,
        public ?int $nSequencial = null,
    ) {
    }
}
