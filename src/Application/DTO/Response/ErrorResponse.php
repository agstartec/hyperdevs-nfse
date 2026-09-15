<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Response;

final readonly class ErrorResponse
{
    /**
     * @param array<int, string> $detalhes
     */
    public function __construct(
        public string $codigo,
        public string $mensagem,
        public array $detalhes = [],
        public bool $recuperavel = false,
    ) {
    }
}
