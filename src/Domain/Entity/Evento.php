<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

use Hyperevs\Nfse\Domain\Contract\EventoInterface;
use Hyperevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperevs\Nfse\Domain\Enum\TipoEvento;
use Hyperevs\Nfse\Domain\ValueObject\ChaveAcesso;

class Evento implements EventoInterface
{
    public function __construct(
        private TipoEvento $tipo,
        private ChaveAcesso $chaveNfse,
        private \DateTimeImmutable $dataEvento,
        private string $versaoAplicacao,
        private int $tipoAmbiente = 2,
        private ?string $cnpjAutor = null,
        private ?string $cpfAutor = null,
        private ?string $codigoMotivo = null,
        private ?string $descricaoMotivo = null,
        private ?string $nSeqEvento = null,
        private ?int $ambGer = null,
        private ?\DateTimeImmutable $dhProc = null,
        private ?string $nDFSe = null,
        private ?string $chSubstituta = null,
        private ?string $cpfAgTrib = null,
        private ?string $nProcAdm = null,
        private ?string $xProcAdm = null,
        private ?string $idEvManifRej = null,
        private ?string $codEventoBloqueio = null,
        private ?string $idBloqOfic = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->versaoAplicacao)) {
            throw new \InvalidArgumentException('Versão da aplicação é obrigatória');
        }

        if (!in_array($this->tipoAmbiente, TipoAmbiente::valores(), true)) {
            throw new \InvalidArgumentException('Tipo de ambiente deve ser 1 (Produção) ou 2 (Homologação)');
        }

        if ($this->cnpjAutor !== null && $this->cpfAutor !== null) {
            throw new \InvalidArgumentException('Informar apenas CNPJ Autor ou CPF Autor, não ambos');
        }

        if ($this->tipo->needsChSubstituta() && empty($this->chSubstituta)) {
            throw new \InvalidArgumentException('Chave da NFS-e substituta (chSubstituta) é obrigatória para substituição');
        }

        if (!empty($this->chSubstituta) && preg_match('/^[0-9]{50}$/', $this->chSubstituta) !== 1) {
            throw new \InvalidArgumentException('chSubstituta deve ter 50 dígitos numéricos');
        }
    }

    public function getTipo(): TipoEvento
    {
        return $this->tipo;
    }

    public function getChaveNfse(): string
    {
        return $this->chaveNfse->getChave();
    }

    public function getChaveAcesso(): ChaveAcesso
    {
        return $this->chaveNfse;
    }

    public function getDataEvento(): \DateTimeImmutable
    {
        return $this->dataEvento;
    }

    public function getVersaoAplicacao(): string
    {
        return $this->versaoAplicacao;
    }

    public function getTipoAmbiente(): int
    {
        return $this->tipoAmbiente;
    }

    public function getCnpjAutor(): ?string
    {
        return $this->cnpjAutor;
    }

    public function getCpfAutor(): ?string
    {
        return $this->cpfAutor;
    }

    public function getCodigoMotivo(): ?string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): ?string
    {
        return $this->descricaoMotivo;
    }

    public function getNSeqEvento(): ?string
    {
        return $this->nSeqEvento;
    }

    public function getAmbGer(): ?int
    {
        return $this->ambGer;
    }

    public function getDhProc(): ?\DateTimeImmutable
    {
        return $this->dhProc;
    }

    public function getNDFSe(): ?string
    {
        return $this->nDFSe;
    }

    public function getChSubstituta(): ?string
    {
        return $this->chSubstituta;
    }

    public function getCpfAgTrib(): ?string
    {
        return $this->cpfAgTrib;
    }

    public function getNProcAdm(): ?string
    {
        return $this->nProcAdm;
    }

    public function getXProcAdm(): ?string
    {
        return $this->xProcAdm;
    }

    public function getIdEvManifRej(): ?string
    {
        return $this->idEvManifRej;
    }

    public function getCodEventoBloqueio(): ?string
    {
        return $this->codEventoBloqueio;
    }

    public function getIdBloqOfic(): ?string
    {
        return $this->idBloqOfic;
    }
}
