<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

class IbsCbsEnderecoExterior
{
    public function __construct(
        private string $cEndPost,
        private string $xCidade,
        private string $xEstProvReg,
    ) {
    }

    public function getCEndPost(): string
    {
        return $this->cEndPost;
    }

    public function getXCidade(): string
    {
        return $this->xCidade;
    }

    public function getXEstProvReg(): string
    {
        return $this->xEstProvReg;
    }
}
