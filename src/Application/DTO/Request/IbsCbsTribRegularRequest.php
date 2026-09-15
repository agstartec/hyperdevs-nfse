<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsTribRegularRequest
{
    public function __construct(
        public string $cstReg,
        public string $cClassTribReg,
    ) {
    }
}
