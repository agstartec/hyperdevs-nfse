<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

class IbsCbsEnderecoObra
{
    private ?string $cep;
    private ?IbsCbsEnderecoExterior $endExt;

    public function __construct(
        ?string $cep,
        ?IbsCbsEnderecoExterior $endExt,
        private string $xLgr,
        private string $nro,
        private string $xBairro,
        private ?string $xCpl = null,
    ) {
        if ($cep === null && $endExt === null) {
            throw new \InvalidArgumentException(
                'Endereço de obra deve informar CEP ou endereço no exterior'
            );
        }
        if ($cep !== null && $endExt !== null) {
            throw new \InvalidArgumentException(
                'Endereço de obra não pode informar CEP e endereço no exterior simultaneamente'
            );
        }

        $this->cep = $cep;
        $this->endExt = $endExt;
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function getEndExt(): ?IbsCbsEnderecoExterior
    {
        return $this->endExt;
    }

    public function getXLgr(): string
    {
        return $this->xLgr;
    }

    public function getNro(): string
    {
        return $this->nro;
    }

    public function getXCpl(): ?string
    {
        return $this->xCpl;
    }

    public function getXBairro(): string
    {
        return $this->xBairro;
    }

    public function isNacional(): bool
    {
        return $this->cep !== null;
    }

    public function isExterior(): bool
    {
        return $this->endExt !== null;
    }
}
