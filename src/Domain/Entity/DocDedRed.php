<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

use DateTimeImmutable;

class DocDedRed
{
    public function __construct(
        private string $tipoDocumento,
        private DateTimeImmutable $dataEmissaoDoc,
        private ?string $chaveNFSe = null,
        private ?string $chaveNFe = null,
        private ?string $codigoMunicipioNFSe = null,
        private ?string $numeroNFSe = null,
        private ?string $codigoVerificacaoNFSe = null,
        private ?string $numeroNFS = null,
        private ?string $modeloNFS = null,
        private ?string $serieNFS = null,
        private ?string $numeroDocFiscal = null,
        private ?string $numeroDoc = null,
        private string $tipoDeducaoReducao = '',
        private ?string $descricaoOutrasDeducoes = null,
        private string $valorDedutivel = '',
        private string $valorDeducao = '',
        private ?IbsCbsFornecedor $fornecedor = null,
    ) {
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function getChaveNFSe(): ?string
    {
        return $this->chaveNFSe;
    }

    public function getChaveNFe(): ?string
    {
        return $this->chaveNFe;
    }

    public function getCodigoMunicipioNFSe(): ?string
    {
        return $this->codigoMunicipioNFSe;
    }

    public function getNumeroNFSe(): ?string
    {
        return $this->numeroNFSe;
    }

    public function getCodigoVerificacaoNFSe(): ?string
    {
        return $this->codigoVerificacaoNFSe;
    }

    public function getNumeroNFS(): ?string
    {
        return $this->numeroNFS;
    }

    public function getModeloNFS(): ?string
    {
        return $this->modeloNFS;
    }

    public function getSerieNFS(): ?string
    {
        return $this->serieNFS;
    }

    public function getNumeroDocFiscal(): ?string
    {
        return $this->numeroDocFiscal;
    }

    public function getNumeroDoc(): ?string
    {
        return $this->numeroDoc;
    }

    public function getTipoDeducaoReducao(): string
    {
        return $this->tipoDeducaoReducao;
    }

    public function getDescricaoOutrasDeducoes(): ?string
    {
        return $this->descricaoOutrasDeducoes;
    }

    public function getDataEmissaoDoc(): DateTimeImmutable
    {
        return $this->dataEmissaoDoc;
    }

    public function getValorDedutivel(): string
    {
        return $this->valorDedutivel;
    }

    public function getValorDeducao(): string
    {
        return $this->valorDeducao;
    }

    public function getFornecedor(): ?IbsCbsFornecedor
    {
        return $this->fornecedor;
    }
}
