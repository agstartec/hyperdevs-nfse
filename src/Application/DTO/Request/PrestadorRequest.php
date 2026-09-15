<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\DTO\Request;

final readonly class PrestadorRequest
{
    public function __construct(
        public ?string $documento,
        public ?bool $isCnpj,
        public ?string $inscricaoMunicipal,
        public string $razaoSocial,
        public ?string $telefone,
        public ?string $email,
        public ?string $logradouro,
        public ?string $numero,
        public ?string $complemento,
        public ?string $bairro,
        public string $codigoMunicipio,
        public ?string $uf,
        public ?string $cep,
        public int $regimeTributario,
        public ?string $nif = null,
        public ?string $caepf = null,
        public ?string $codigoNaoNif = null,
        public ?int $regEspTrib = null,
        public ?int $regApTribSN = null,
    ) {
    }
}
