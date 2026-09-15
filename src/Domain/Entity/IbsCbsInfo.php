<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\Enum\FinalidadeNfse;
use Hyperdevs\Nfse\Domain\Enum\IndicadorDestinacao;
use Hyperdevs\Nfse\Domain\Enum\IndicadorFinal;
use Hyperdevs\Nfse\Domain\Enum\TipoEnteGovernamental;
use Hyperdevs\Nfse\Domain\Enum\TipoOperacao;
use Hyperdevs\Nfse\Domain\ValueObject\ChaveAcesso;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoClassificacaoTributaria;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoCreditoPresumido;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoIndicadorOperacao;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoSituacaoTributaria;

class IbsCbsInfo
{
    /** @param ChaveAcesso[] $refNFSeList */
    public function __construct(
        private FinalidadeNfse $finNFSe,
        private CodigoIndicadorOperacao $cIndOp,
        private IndicadorDestinacao $indDest,
        private CodigoSituacaoTributaria $cst,
        private CodigoClassificacaoTributaria $cClassTrib,
        private ?IndicadorFinal $indFinal = null,
        private ?TipoOperacao $tpOper = null,
        private ?TipoEnteGovernamental $tpEnteGov = null,
        private ?CodigoCreditoPresumido $cCredPres = null,
        private ?IbsCbsDest $dest = null,
        private ?IbsCbsTribRegular $tribRegular = null,
        private ?IbsCbsDiferimento $diferimento = null,
        private ?array $refNFSeList = null,
        private ?IbsCbsImovel $imovel = null,
        private ?IbsCbsReeRepRes $reeRepRes = null,
    ) {
    }

    public function getFinNFSe(): FinalidadeNfse
    {
        return $this->finNFSe;
    }

    public function getCIndOp(): CodigoIndicadorOperacao
    {
        return $this->cIndOp;
    }

    public function getIndDest(): IndicadorDestinacao
    {
        return $this->indDest;
    }

    public function getCst(): CodigoSituacaoTributaria
    {
        return $this->cst;
    }

    public function getCClassTrib(): CodigoClassificacaoTributaria
    {
        return $this->cClassTrib;
    }

    public function getIndFinal(): ?IndicadorFinal
    {
        return $this->indFinal;
    }

    public function getTpOper(): ?TipoOperacao
    {
        return $this->tpOper;
    }

    public function getTpEnteGov(): ?TipoEnteGovernamental
    {
        return $this->tpEnteGov;
    }

    public function getCCredPres(): ?CodigoCreditoPresumido
    {
        return $this->cCredPres;
    }

    public function getDest(): ?IbsCbsDest
    {
        return $this->dest;
    }

    public function getTribRegular(): ?IbsCbsTribRegular
    {
        return $this->tribRegular;
    }

    public function getDiferimento(): ?IbsCbsDiferimento
    {
        return $this->diferimento;
    }

    /** @return ChaveAcesso[]|null */
    public function getRefNFSeList(): ?array
    {
        return $this->refNFSeList;
    }

    public function hasRefNFSe(): bool
    {
        return $this->refNFSeList !== null && $this->refNFSeList !== [];
    }

    public function getImovel(): ?IbsCbsImovel
    {
        return $this->imovel;
    }

    public function getReeRepRes(): ?IbsCbsReeRepRes
    {
        return $this->reeRepRes;
    }
}
