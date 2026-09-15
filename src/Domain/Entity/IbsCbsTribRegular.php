<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\ValueObject\CodigoClassificacaoTributaria;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoSituacaoTributaria;

class IbsCbsTribRegular
{
    public function __construct(
        private CodigoSituacaoTributaria $cstReg,
        private CodigoClassificacaoTributaria $cClassTribReg,
    ) {
    }

    public function getCstReg(): CodigoSituacaoTributaria
    {
        return $this->cstReg;
    }

    public function getCClassTribReg(): CodigoClassificacaoTributaria
    {
        return $this->cClassTribReg;
    }
}
