<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class EnderecoRequest
{
    public function __construct(
        public ?string $logradouro = null,
        public ?string $numero = null,
        public ?string $complemento = null,
        public ?string $bairro = null,
        public ?string $codigoMunicipio = null,
        public ?string $uf = null,
        public ?string $cep = null,
        public ?string $codigoPais = null,
        public ?string $codigoPostalExterior = null,
        public ?string $nomeCidadeExterior = null,
        public ?string $estadoProvinciaExterior = null,
    ) {
    }
}
