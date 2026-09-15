<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\ValueObject;

final readonly class CstClassTribProperties
{
    public function __construct(
        private string $cClassTrib,
        private string $cst,
        private string $descricao,
        private bool $validoParaNfse,
        private bool $permiteDiferimento,
        private bool $exigeGrupoTributacaoRegular,
        private ?float $pRedIBS = null,
        private ?float $pRedCBS = null,
    ) {
    }

    public function getCClassTrib(): string
    {
        return $this->cClassTrib;
    }

    public function getCst(): string
    {
        return $this->cst;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function isValidoParaNfse(): bool
    {
        return $this->validoParaNfse;
    }

    public function isPermiteDiferimento(): bool
    {
        return $this->permiteDiferimento;
    }

    public function isExigeGrupoTributacaoRegular(): bool
    {
        return $this->exigeGrupoTributacaoRegular;
    }

    public function getPRedIBS(): ?float
    {
        return $this->pRedIBS;
    }

    public function getPRedCBS(): ?float
    {
        return $this->pRedCBS;
    }

    public function hasReducaoIBS(): bool
    {
        return $this->pRedIBS !== null && $this->pRedIBS > 0.0;
    }

    public function hasReducaoCBS(): bool
    {
        return $this->pRedCBS !== null && $this->pRedCBS > 0.0;
    }
}
