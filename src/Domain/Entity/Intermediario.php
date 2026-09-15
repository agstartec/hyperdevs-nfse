<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\ValueObject\Cnpj;
use Hyperdevs\Nfse\Domain\ValueObject\Cpf;
use Hyperdevs\Nfse\Domain\ValueObject\Email;
use Hyperdevs\Nfse\Domain\ValueObject\Telefone;

class Intermediario
{
    public function __construct(
        private Cnpj|Cpf|null $documento,
        private string $razaoSocial,
        private ?string $inscricaoMunicipal,
        private ?Telefone $telefone,
        private ?Email $email,
        private ?Endereco $endereco,
        private ?string $nif = null,
        private ?string $codigoNaoNif = null,
        private ?string $caepf = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social do intermediário é obrigatória');
        }

        if (strlen($this->razaoSocial) > 150) {
            throw new \InvalidArgumentException('Razão social do intermediário deve ter no máximo 150 caracteres');
        }
    }

    public function getDocumento(): Cnpj|Cpf|null
    {
        return $this->documento;
    }

    public function isCnpj(): bool
    {
        return $this->documento instanceof Cnpj;
    }

    public function getCnpj(): ?Cnpj
    {
        if (!$this->documento instanceof Cnpj) {
            return null;
        }
        return $this->documento;
    }

    public function getCpf(): ?Cpf
    {
        if (!$this->documento instanceof Cpf) {
            return null;
        }
        return $this->documento;
    }

    public function getRazaoSocial(): string
    {
        return $this->razaoSocial;
    }

    public function getInscricaoMunicipal(): ?string
    {
        return $this->inscricaoMunicipal;
    }

    public function getTelefone(): ?Telefone
    {
        return $this->telefone;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getEndereco(): ?Endereco
    {
        return $this->endereco;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function getCodigoNaoNif(): ?string
    {
        return $this->codigoNaoNif;
    }

    public function getCaepf(): ?string
    {
        return $this->caepf;
    }
}
