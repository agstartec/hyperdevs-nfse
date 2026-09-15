<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class EventoRequest
{
    public function __construct(
        public int $tipoAmbiente,
        public string $versaoAplicacao,
        public string $dataEvento,
        public string $chaveNfse,
        public string $tipoEvento,
        public ?string $cnpjAutor = null,
        public ?string $cpfAutor = null,
        public ?string $codigoMotivo = null,
        public ?string $descricaoMotivo = null,
        public ?string $nSeqEvento = null,
        public ?int $ambGer = null,
        public ?string $dhProc = null,
        public ?string $nDFSe = null,
        public ?string $chSubstituta = null,
        public ?string $cpfAgTrib = null,
        public ?string $nProcAdm = null,
        public ?string $xProcAdm = null,
        public ?string $idEvManifRej = null,
        public ?string $codEventoBloqueio = null,
        public ?string $idBloqOfic = null,
    ) {
    }
}
