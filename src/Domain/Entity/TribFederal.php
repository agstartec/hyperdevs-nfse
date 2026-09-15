<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

class TribFederal
{
    public function __construct(
        private ?string $pisCofinsCst = null,
        private ?string $pisCofinsTipo = null,
        private ?float $pisCofinsAliquotaPis = null,
        private ?float $pisCofinsAliquotaCofins = null,
        private ?string $valorRetidoCP = null,
        private ?string $valorRetidoIRRF = null,
        private ?string $valorRetidoCSLL = null,
        private ?string $pisCofinsBaseCalculo = null,
        private ?string $valorPis = null,
        private ?string $valorCofins = null,
    ) {
    }

    public function getPisCofinsCst(): ?string
    {
        return $this->pisCofinsCst;
    }

    public function getPisCofinsTipo(): ?string
    {
        return $this->pisCofinsTipo;
    }

    public function getPisCofinsAliquotaPis(): ?float
    {
        return $this->pisCofinsAliquotaPis;
    }

    public function getPisCofinsAliquotaCofins(): ?float
    {
        return $this->pisCofinsAliquotaCofins;
    }

    public function getValorRetidoCP(): ?string
    {
        return $this->valorRetidoCP;
    }

    public function getValorRetidoIRRF(): ?string
    {
        return $this->valorRetidoIRRF;
    }

    public function getValorRetidoCSLL(): ?string
    {
        return $this->valorRetidoCSLL;
    }

    /** Base de cálculo do PIS/COFINS de apuração própria (débito), não retenção. */
    public function getPisCofinsBaseCalculo(): ?string
    {
        return $this->pisCofinsBaseCalculo;
    }

    /** Valor do débito de PIS de apuração própria. Não usar para PIS retido (vai em vRetCSLL). */
    public function getValorPis(): ?string
    {
        return $this->valorPis;
    }

    /** Valor do débito de COFINS de apuração própria. Não usar para COFINS retido (vai em vRetCSLL). */
    public function getValorCofins(): ?string
    {
        return $this->valorCofins;
    }
}
