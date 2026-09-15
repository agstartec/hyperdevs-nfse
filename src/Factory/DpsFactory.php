<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Factory;

use Hyperdevs\Nfse\Domain\Entity\Dps;
use Hyperdevs\Nfse\Domain\Entity\Endereco;
use Hyperdevs\Nfse\Domain\Entity\Prestador;
use Hyperdevs\Nfse\Domain\Entity\Servico;
use Hyperdevs\Nfse\Domain\Entity\Tomador;
use Hyperdevs\Nfse\Domain\Enum\RegimeTributario;
use Hyperdevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperdevs\Nfse\Domain\Enum\TipoEmitente;
use Hyperdevs\Nfse\Domain\ValueObject\Cep;
use Hyperdevs\Nfse\Domain\ValueObject\Cnpj;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoMunicipio;
use Hyperdevs\Nfse\Domain\ValueObject\Cpf;
use Hyperdevs\Nfse\Domain\ValueObject\Money;

class DpsFactory
{
    /**
     * @param array<string, mixed> $data
     */
    public static function create(
        array $data,
    ): Dps {
        $prestadorData = $data['prestador'];
        $tomadorData = $data['tomador'];
        $servicoData = $data['servico'];

        $prestador = new Prestador(
            documento: isset($prestadorData['cnpj']) ? new Cnpj($prestadorData['cnpj']) : (isset($prestadorData['cpf']) ? new Cpf($prestadorData['cpf']) : null),
            inscricaoMunicipal: $prestadorData['inscricaoMunicipal'] ?? null,
            razaoSocial: $prestadorData['razaoSocial'],
            telefone: null,
            email: null,
            endereco: self::createEndereco($prestadorData['endereco']),
            regimeTributario: RegimeTributario::from($prestadorData['regimeTributario']),
            nif: $prestadorData['nif'] ?? null,
            caepf: $prestadorData['caepf'] ?? null,
            codigoNaoNif: $prestadorData['codigoNaoNif'] ?? null,
        );

        $tomador = new Tomador(
            documento: isset($tomadorData['cnpj']) ? new Cnpj($tomadorData['cnpj']) : (isset($tomadorData['cpf']) ? new Cpf($tomadorData['cpf']) : null),
            razaoSocial: $tomadorData['razaoSocial'],
            telefone: null,
            email: null,
            endereco: self::createEndereco($tomadorData['endereco']),
            nif: $tomadorData['nif'] ?? null,
            inscricaoMunicipal: $tomadorData['inscricaoMunicipal'] ?? null,
        );

        $servico = new Servico(
            discriminacao: $servicoData['discriminacao'],
            codigoTributacao: $servicoData['codigoTributacao'],
            localPrestacao: new CodigoMunicipio($servicoData['codigoMunicipioPrestacao']),
            valorServicos: new Money($servicoData['valorServicos']),
            descontoIncondicionado: new Money($servicoData['descontoIncondicionado'] ?? 0),
            descontoCondicionado: new Money($servicoData['descontoCondicionado'] ?? 0),
            aliquotaIss: $servicoData['aliquotaIss'],
            codigoNbs: $servicoData['codigoNbs'] ?? null,
        );

        return new Dps(
            tipoAmbiente: TipoAmbiente::from($data['tipoAmbiente']),
            dataEmissao: new \DateTimeImmutable($data['dataEmissao']),
            versaoAplicacao: $data['versaoAplicacao'],
            serie: $data['serie'],
            numero: $data['numero'],
            dataCompetencia: new \DateTimeImmutable($data['dataCompetencia']),
            tipoEmissao: TipoEmitente::from($data['tipoEmissao']),
            codigoMunicipioEmissor: new CodigoMunicipio($data['codigoMunicipioEmissor']),
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function createEndereco(array $data): Endereco
    {
        return new Endereco(
            logradouro: $data['logradouro'],
            numero: $data['numero'],
            complemento: $data['complemento'] ?? null,
            bairro: $data['bairro'],
            codigoMunicipio: new CodigoMunicipio($data['codigoMunicipio']),
            cep: new Cep($data['cep']),
        );
    }
}
