<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Response;

final readonly class EventoResponse
{
    /**
     * @param array<string, mixed>|null $dados
     * @param list<array{codigo: string|null, descricao: string}> $erros lista completa dos erros
     *        estruturados da SEFIN; `mensagem` traz apenas o primeiro. Vazia em caso de sucesso.
     */
    public function __construct(
        public bool $success,
        public ?string $mensagem = null,
        public ?array $dados = null,
        public array $erros = [],
    ) {
    }
}
