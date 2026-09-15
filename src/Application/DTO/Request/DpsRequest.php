<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class DpsRequest
{
    public function __construct(
        public int $tipoAmbiente,
        public string $dataEmissao,
        public string $versaoAplicacao,
        public int $serie,
        public int $numero,
        public string $dataCompetencia,
        public int $tipoEmissao,
        public string $codigoMunicipioEmissor,
        public PrestadorRequest $prestador,
        public ServicoRequest $servico,
        public ?TomadorRequest $tomador = null,
        public ?SubstituicaoRequest $substituicao = null,
        public ?IntermediarioRequest $intermediario = null,
        public ?IbsCbsRequest $ibscbs = null,
        public ?int $cMotivoEmisTI = null,
        public ?string $chNFSeRej = null,
    ) {
    }
}
