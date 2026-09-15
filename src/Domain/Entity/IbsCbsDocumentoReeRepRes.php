<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\Enum\TipoReembolsoRepasseRessarcimento;

class IbsCbsDocumentoReeRepRes
{
    public function __construct(
        private string $tipo,
        private \DateTimeInterface $dtEmiDoc,
        private \DateTimeInterface $dtCompDoc,
        private TipoReembolsoRepasseRessarcimento $tpReeRepRes,
        private string $vlrReeRepRes,
        private ?IbsCbsFornecedor $fornec = null,
        private ?string $xTpReeRepRes = null,
        private ?string $tipoChaveDFe = null,
        private ?string $xTipoChaveDFe = null,
        private ?string $chaveDFe = null,
        private ?string $cMunDocFiscal = null,
        private ?string $nDocFiscal = null,
        private ?string $xDocFiscal = null,
        private ?string $nDoc = null,
        private ?string $xDoc = null,
    ) {
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getDtEmiDoc(): \DateTimeInterface
    {
        return $this->dtEmiDoc;
    }

    public function getDtCompDoc(): \DateTimeInterface
    {
        return $this->dtCompDoc;
    }

    public function getTpReeRepRes(): TipoReembolsoRepasseRessarcimento
    {
        return $this->tpReeRepRes;
    }

    public function getVlrReeRepRes(): string
    {
        return $this->vlrReeRepRes;
    }

    public function getFornec(): ?IbsCbsFornecedor
    {
        return $this->fornec;
    }

    public function getXTpReeRepRes(): ?string
    {
        return $this->xTpReeRepRes;
    }

    public function getTipoChaveDFe(): ?string
    {
        return $this->tipoChaveDFe;
    }

    public function getXTipoChaveDFe(): ?string
    {
        return $this->xTipoChaveDFe;
    }

    public function getChaveDFe(): ?string
    {
        return $this->chaveDFe;
    }

    public function getCMunDocFiscal(): ?string
    {
        return $this->cMunDocFiscal;
    }

    public function getNDocFiscal(): ?string
    {
        return $this->nDocFiscal;
    }

    public function getXDocFiscal(): ?string
    {
        return $this->xDocFiscal;
    }

    public function getNDoc(): ?string
    {
        return $this->nDoc;
    }

    public function getXDoc(): ?string
    {
        return $this->xDoc;
    }
}
