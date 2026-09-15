<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Service;

use Hyperevs\Nfse\Domain\Entity\Dps;

/**
 * Domain Service para geração do identificador da DPS.
 *
 * Regra oficial TSIdDPS (XSD v1.01):
 * "DPS" + Cód.Mun (7) + Tipo de Inscrição Federal (1)
 * + Inscrição Federal (14) + Série (5) + Núm. DPS (15)
 * Total: 45 caracteres
 *
 * Tipo de Inscrição Federal:
 *  1 = CPF (inscrição com 11 dígitos + 3 zeros à esquerda = 14)
 *  2 = CNPJ (inscrição com 14 dígitos)
 */
final readonly class DpsIdService
{
    public function generate(Dps $dps): string
    {
        $codigoMunicipio = $dps->getCodigoMunicipioEmissor()->getCodigo();
        $prestador = $dps->getPrestador();

        if ($prestador->getCnpj() !== null) {
            $tipoInscricao = '2';
            $inscricaoFederal = $prestador->getCnpj()->getNumero();
        } elseif ($prestador->getCpf() !== null) {
            $tipoInscricao = '1';
            $inscricaoFederal = str_pad($prestador->getCpf()->getNumero(), 14, '0', STR_PAD_LEFT);
        } else {
            throw new \RuntimeException('Prestador deve ter CNPJ ou CPF para gerar Id da DPS');
        }

        $serie = str_pad((string) $dps->getSerie(), 5, '0', STR_PAD_LEFT);
        $numero = str_pad((string) $dps->getNumero(), 15, '0', STR_PAD_LEFT);

        return 'DPS'
            . $codigoMunicipio
            . $tipoInscricao
            . $inscricaoFederal
            . $serie
            . $numero;
    }
}
