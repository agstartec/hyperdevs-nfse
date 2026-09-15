<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

class InfoCompl
{
    /**
     * @param array<int, string>|null $itensPedido
     */
    public function __construct(
        private ?string $idDocTecnico = null,
        private ?string $docReferencia = null,
        private ?string $numeroPedido = null,
        private ?array $itensPedido = null,
        private ?string $infoComplementar = null,
    ) {
    }

    public function getIdDocTecnico(): ?string
    {
        return $this->idDocTecnico;
    }

    public function getDocReferencia(): ?string
    {
        return $this->docReferencia;
    }

    public function getNumeroPedido(): ?string
    {
        return $this->numeroPedido;
    }

    /**
     * @return array<int, string>|null
     */
    public function getItensPedido(): ?array
    {
        return $this->itensPedido;
    }

    public function getInfoComplementar(): ?string
    {
        return $this->infoComplementar;
    }
}
