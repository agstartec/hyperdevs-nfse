<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsReeRepResRequest
{
    /** @param IbsCbsDocumentoReeRepResRequest[] $documentos */
    public function __construct(
        public array $documentos,
    ) {
    }
}
