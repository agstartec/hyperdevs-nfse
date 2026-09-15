<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class IntermediarioRequest
{
    public function __construct(
        public ?string $documento,
        public bool $isCnpj,
        public string $razaoSocial,
        public ?string $telefone,
        public ?string $email,
        public ?string $logradouro,
        public ?string $numero,
        public ?string $complemento,
        public ?string $bairro,
        public ?string $codigoMunicipio,
        public ?string $uf,
        public ?string $cep,
        public ?string $nif = null,
        public ?string $inscricaoMunicipal = null,
        public ?string $codigoNaoNif = null,
        public ?string $caepf = null,
        public ?string $codigoPais = null,
        public ?string $codigoPostalExterior = null,
        public ?string $nomeCidadeExterior = null,
        public ?string $estadoProvinciaExterior = null,
    ) {
    }
}
