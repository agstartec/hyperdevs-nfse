<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\ValueObject\Cep;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoMunicipio;

class Endereco
{
    public function __construct(
        private string $logradouro,
        private string $numero,
        private ?string $complemento,
        private string $bairro,
        private ?CodigoMunicipio $codigoMunicipio,
        private ?Cep $cep,
        private ?string $codigoPais = null,
        private ?string $nomeCidadeExterior = null,
        private ?string $estadoProvinciaExterior = null,
        private ?string $codigoPostalExterior = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->logradouro)) {
            throw new \InvalidArgumentException('Logradouro é obrigatório');
        }

        if (empty($this->bairro)) {
            throw new \InvalidArgumentException('Bairro é obrigatório');
        }

        // Choice do XSD (TCEndereco): endNac exige cMun+CEP; endExt não os usa.
        if (!$this->isExterior() && ($this->codigoMunicipio === null || $this->cep === null)) {
            throw new \InvalidArgumentException(
                'Endereço nacional exige código de município (cMun) e CEP'
            );
        }
    }

    public function getLogradouro(): string
    {
        return $this->logradouro;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getComplemento(): ?string
    {
        return $this->complemento;
    }

    public function getBairro(): string
    {
        return $this->bairro;
    }

    public function getCodigoMunicipio(): ?CodigoMunicipio
    {
        return $this->codigoMunicipio;
    }

    public function getCep(): ?Cep
    {
        return $this->cep;
    }

    public function getCodigoPais(): ?string
    {
        return $this->codigoPais;
    }

    public function getNomeCidadeExterior(): ?string
    {
        return $this->nomeCidadeExterior;
    }

    public function getEstadoProvinciaExterior(): ?string
    {
        return $this->estadoProvinciaExterior;
    }

    public function getCodigoPostalExterior(): ?string
    {
        return $this->codigoPostalExterior;
    }

    public function isExterior(): bool
    {
        return $this->codigoPais !== null;
    }
}
