<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

use Hyperevs\Nfse\Domain\ValueObject\Cnpj;
use Hyperevs\Nfse\Domain\ValueObject\Cpf;
use Hyperevs\Nfse\Domain\ValueObject\Nif;

class IbsCbsFornecedor
{
    public function __construct(
        private ?Cnpj $cnpj = null,
        private ?Cpf $cpf = null,
        private ?Nif $nif = null,
        private ?string $codigoNaoNif = null,
        private string $xNome = '',
    ) {
        if (trim($this->xNome) === '') {
            throw new \InvalidArgumentException('xNome do fornecedor é obrigatório');
        }
    }

    public function getCnpj(): ?Cnpj
    {
        return $this->cnpj;
    }

    public function getCpf(): ?Cpf
    {
        return $this->cpf;
    }

    public function getNif(): ?Nif
    {
        return $this->nif;
    }

    public function getCodigoNaoNif(): ?string
    {
        return $this->codigoNaoNif;
    }

    public function getXNome(): string
    {
        return $this->xNome;
    }
}
