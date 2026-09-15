<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\Enum\MotivoEmissaoTI;
use Hyperdevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperdevs\Nfse\Domain\Enum\TipoEmitente;
use Hyperdevs\Nfse\Domain\Enum\VersaoSchema;
use Hyperdevs\Nfse\Domain\ValueObject\ChaveAcesso;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoMunicipio;

class Dps
{
    private ?ChaveAcesso $chaveAcesso = null;

    public function __construct(
        private TipoAmbiente $tipoAmbiente,
        private \DateTimeImmutable $dataEmissao,
        private string $versaoAplicacao,
        private int $serie,
        private int $numero,
        private \DateTimeImmutable $dataCompetencia,
        private TipoEmitente $tipoEmissao,
        private CodigoMunicipio $codigoMunicipioEmissor,
        private Prestador $prestador,
        private Servico $servico,
        private ?Tomador $tomador = null,
        private VersaoSchema $versao = VersaoSchema::V1_01,
        private ?Intermediario $intermediario = null,
        private ?Substituicao $substituicao = null,
        private ?IbsCbsInfo $ibscbs = null,
        private ?MotivoEmissaoTI $cMotivoEmisTI = null,
        private ?ChaveAcesso $chNFSeRej = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->numero <= 0) {
            throw new \InvalidArgumentException('Número da DPS deve ser maior que zero');
        }

        if ($this->serie <= 0) {
            throw new \InvalidArgumentException('Série da DPS deve ser maior que zero');
        }

        if ($this->cMotivoEmisTI === null && $this->tipoEmissao !== TipoEmitente::PRESTADOR) {
            throw new \InvalidArgumentException('cMotivoEmisTI é obrigatório quando o emitente é Tomador ou Intermediário');
        }

        if ($this->chNFSeRej !== null && $this->cMotivoEmisTI !== MotivoEmissaoTI::REJEICAO_NFSE_PRESTADOR) {
            throw new \InvalidArgumentException('chNFSeRej só é permitido quando cMotivoEmisTI = 4 (rejeição de NFS-e do prestador)');
        }

        if ($this->chNFSeRej === null && $this->cMotivoEmisTI === MotivoEmissaoTI::REJEICAO_NFSE_PRESTADOR) {
            throw new \InvalidArgumentException('chNFSeRej é obrigatório quando cMotivoEmisTI = 4 (rejeição de NFS-e do prestador)');
        }
    }

    public function gerarChaveAcesso(): ChaveAcesso
    {
        if ($this->chaveAcesso !== null) {
            return $this->chaveAcesso;
        }

        $documento = $this->prestador->getDocumento();
        if ($documento === null) {
            throw new \RuntimeException('Prestador sem CNPJ/CPF não pode gerar chave de acesso');
        }

        $docStr = $documento->__toString();
        $tpInsc = strlen($docStr) === 14 ? '2' : '1';

        $codigo = sprintf(
            '%s%s%s%05d%015d00000000',
            $this->codigoMunicipioEmissor->getCodigo(),
            $tpInsc,
            $docStr,
            $this->serie,
            $this->numero
        );

        $this->chaveAcesso = new ChaveAcesso($codigo);
        return $this->chaveAcesso;
    }

    public function getChaveAcesso(): ?ChaveAcesso
    {
        return $this->chaveAcesso;
    }

    public function getTipoAmbiente(): TipoAmbiente
    {
        return $this->tipoAmbiente;
    }

    public function getDataEmissao(): \DateTimeImmutable
    {
        return $this->dataEmissao;
    }

    public function getVersaoAplicacao(): string
    {
        return $this->versaoAplicacao;
    }

    public function getSerie(): int
    {
        return $this->serie;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getDataCompetencia(): \DateTimeImmutable
    {
        return $this->dataCompetencia;
    }

    public function getTipoEmitente(): TipoEmitente
    {
        return $this->tipoEmissao;
    }

    public function getCodigoMunicipioEmissor(): CodigoMunicipio
    {
        return $this->codigoMunicipioEmissor;
    }

    public function getPrestador(): Prestador
    {
        return $this->prestador;
    }

    public function getTomador(): ?Tomador
    {
        return $this->tomador;
    }

    public function getServico(): Servico
    {
        return $this->servico;
    }

    public function getVersao(): VersaoSchema
    {
        return $this->versao;
    }

    public function getIntermediario(): ?Intermediario
    {
        return $this->intermediario;
    }

    public function getSubstituicao(): ?Substituicao
    {
        return $this->substituicao;
    }

    public function getIbsCbs(): ?IbsCbsInfo
    {
        return $this->ibscbs;
    }

    public function getCMotivoEmisTI(): ?MotivoEmissaoTI
    {
        return $this->cMotivoEmisTI;
    }

    public function getChNFSeRej(): ?ChaveAcesso
    {
        return $this->chNFSeRej;
    }
}
