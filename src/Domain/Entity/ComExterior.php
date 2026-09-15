<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

class ComExterior
{
    public function __construct(
        private int $modoPrestacao,
        private int $vinculoPrestador,
        private string $codigoMoeda,
        private float $valorServicoMoeda,
        private string $mecanismoApoioPrestador,
        private string $mecanismoApoioTomador,
        private string $movimentacaoTemporaria,
        private string $enviarMDIC,
        private ?string $numeroDeclaracaoImportacao = null,
        private ?string $numeroRegistroExportacao = null,
    ) {
    }

    public function getModoPrestacao(): int
    {
        return $this->modoPrestacao;
    }

    public function getVinculoPrestador(): int
    {
        return $this->vinculoPrestador;
    }

    public function getCodigoMoeda(): string
    {
        return $this->codigoMoeda;
    }

    public function getValorServicoMoeda(): float
    {
        return $this->valorServicoMoeda;
    }

    public function getMecanismoApoioPrestador(): string
    {
        return $this->mecanismoApoioPrestador;
    }

    public function getMecanismoApoioTomador(): string
    {
        return $this->mecanismoApoioTomador;
    }

    public function getMovimentacaoTemporaria(): string
    {
        return $this->movimentacaoTemporaria;
    }

    public function getEnviarMDIC(): string
    {
        return $this->enviarMDIC;
    }

    public function getNumeroDeclaracaoImportacao(): ?string
    {
        return $this->numeroDeclaracaoImportacao;
    }

    public function getNumeroRegistroExportacao(): ?string
    {
        return $this->numeroRegistroExportacao;
    }
}
