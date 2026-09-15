<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class ComExteriorRequest
{
    public function __construct(
        public ?int $modoPrestacao = null,
        public ?int $vinculoPrestador = null,
        public ?string $codigoMoeda = null,
        public ?float $valorServicoMoeda = null,
        public ?string $mecanismoApoioPrestador = null,
        public ?string $mecanismoApoioTomador = null,
        public ?string $movimentacaoTemporaria = null,
        public ?string $enviarMDIC = null,
        public ?string $numeroDeclaracaoImportacao = null,
        public ?string $numeroRegistroExportacao = null,
    ) {
    }
}
