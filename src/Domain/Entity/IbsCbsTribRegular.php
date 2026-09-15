<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

use Hyperevs\Nfse\Domain\ValueObject\CodigoClassificacaoTributaria;
use Hyperevs\Nfse\Domain\ValueObject\CodigoSituacaoTributaria;

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
