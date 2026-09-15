<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

class IbsCbsReeRepRes
{
    /** @param IbsCbsDocumentoReeRepRes[] $documentos */
    public function __construct(
        private array $documentos,
    ) {
    }

    /** @return IbsCbsDocumentoReeRepRes[] */
    public function getDocumentos(): array
    {
        return $this->documentos;
    }
}
