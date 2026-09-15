<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class DocDedRedRequest
{
    public function __construct(
        public ?string $tipoDocumento = null,
        public ?string $chaveNFSe = null,
        public ?string $chaveNFe = null,
        public ?string $codigoMunicipioNFSe = null,
        public ?string $numeroNFSe = null,
        public ?string $codigoVerificacaoNFSe = null,
        public ?string $numeroNFS = null,
        public ?string $modeloNFS = null,
        public ?string $serieNFS = null,
        public ?string $numeroDocFiscal = null,
        public ?string $numeroDoc = null,
        public ?string $tipoDeducaoReducao = null,
        public ?string $descricaoOutrasDeducoes = null,
        public ?string $dataEmissaoDoc = null,
        public ?string $valorDedutivel = null,
        public ?string $valorDeducao = null,
        public ?\Hyperevs\Nfse\Application\DTO\Request\IbsCbsFornecedorRequest $fornecedor = null,
    ) {
    }
}
