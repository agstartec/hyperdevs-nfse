<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Response;

final readonly class DpsResponse
{
    /**
     * @param array<string, mixed>|null $dados
     */
    public function __construct(
        public bool $success,
        public ?string $chaveAcesso = null,
        public ?array $dados = null,
        public ?string $mensagem = null,
    ) {
    }
}
