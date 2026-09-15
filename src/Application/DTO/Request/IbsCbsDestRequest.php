<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\DTO\Request;

final readonly class IbsCbsDestRequest
{
    public function __construct(
        public ?string $cnpj = null,
        public ?string $cpf = null,
        public ?string $nif = null,
        public ?string $codigoNaoNif = null,
        public string $xNome = '',
        public ?string $logradouro = null,
        public ?string $numero = null,
        public ?string $complemento = null,
        public ?string $bairro = null,
        public ?string $codigoMunicipio = null,
        public ?string $uf = null,
        public ?string $cep = null,
        public ?string $fone = null,
        public ?string $email = null,
        public ?string $codigoPais = null,
        public ?string $codigoPostalExterior = null,
        public ?string $nomeCidadeExterior = null,
        public ?string $estadoProvinciaExterior = null,
    ) {
    }
}
