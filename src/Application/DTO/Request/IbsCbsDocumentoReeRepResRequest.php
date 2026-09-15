<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsDocumentoReeRepResRequest
{
    public function __construct(
        public string $tipoDocumento,
        public string $dtEmiDoc,
        public string $dtCompDoc,
        public string $tpReeRepRes,
        public float $vlrReeRepRes,
        public ?string $tipoChaveDFe = null,
        public ?string $xTipoChaveDFe = null,
        public ?string $chaveDFe = null,
        public ?string $cMunDocFiscal = null,
        public ?string $nDocFiscal = null,
        public ?string $xDocFiscal = null,
        public ?string $nDoc = null,
        public ?string $xDoc = null,
        public ?IbsCbsFornecedorRequest $fornec = null,
        public ?string $xTpReeRepRes = null,
    ) {
    }
}
