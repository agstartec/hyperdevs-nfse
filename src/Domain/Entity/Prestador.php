<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\Enum\RegimeEspecialTributacao;
use Hyperdevs\Nfse\Domain\Enum\RegimeTributario;
use Hyperdevs\Nfse\Domain\ValueObject\Cnpj;
use Hyperdevs\Nfse\Domain\ValueObject\Cpf;
use Hyperdevs\Nfse\Domain\ValueObject\Email;
use Hyperdevs\Nfse\Domain\ValueObject\Telefone;

class Prestador
{
    public function __construct(
        private Cnpj|Cpf|null $documento,
        private ?string $inscricaoMunicipal,
        private string $razaoSocial,
        private ?Telefone $telefone,
        private ?Email $email,
        private ?Endereco $endereco,
        private RegimeTributario $regimeTributario,
        private RegimeEspecialTributacao $regimeEspecialTributacao = RegimeEspecialTributacao::NENHUM,
        private ?string $nif = null,
        private ?string $caepf = null,
        private ?string $codigoNaoNif = null,
        private ?int $regimeApuracaoSimplesNacional = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social é obrigatória');
        }

        if (strlen($this->razaoSocial) > 150) {
            throw new \InvalidArgumentException('Razão social deve ter no máximo 150 caracteres');
        }

        if ($this->documento === null && $this->nif === null && $this->codigoNaoNif === null) {
            throw new \InvalidArgumentException('Prestador deve ter CNPJ, CPF, NIF ou cNaoNIF');
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

    public function getInscricaoMunicipal(): ?string
    {
        return $this->inscricaoMunicipal;
    }

    public function getRazaoSocial(): string
    {
        return $this->razaoSocial;
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

    public function getRegimeTributario(): RegimeTributario
    {
        return $this->regimeTributario;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function getCaepf(): ?string
    {
        return $this->caepf;
    }

    public function getCodigoNaoNif(): ?string
    {
        return $this->codigoNaoNif;
    }

    public function getRegimeEspecialTributacao(): RegimeEspecialTributacao
    {
        return $this->regimeEspecialTributacao;
    }

    public function getRegimeApuracaoSimplesNacional(): ?int
    {
        return $this->regimeApuracaoSimplesNacional;
    }
}
