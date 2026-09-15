<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class InfoComplRequest
{
    /**
     * @param array<int, string>|null $itensPedido
     */
    public function __construct(
        public ?string $idDocTecnico = null,
        public ?string $docReferencia = null,
        public ?string $numeroPedido = null,
        public ?array $itensPedido = null,
        public ?string $infoComplementar = null,
    ) {
    }

    /**
     * @return array<int, string>|null
     */
    public function getItensPedido(): ?array
    {
        return $this->itensPedido;
    }

}
