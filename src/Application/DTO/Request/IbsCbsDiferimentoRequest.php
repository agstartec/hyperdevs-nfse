<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsDiferimentoRequest
{
    public function __construct(
        public float $pDifUF,
        public float $pDifMun,
        public float $pDifCBS,
    ) {
    }
}
