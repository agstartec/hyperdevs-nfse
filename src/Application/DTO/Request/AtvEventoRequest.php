<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class AtvEventoRequest
{
    public function __construct(
        public ?string $descricao = null,
        public ?string $dataInicio = null,
        public ?string $dataFim = null,
        public ?string $identificacaoEvento = null,
        public ?EnderecoRequest $endereco = null,
    ) {
    }
}
