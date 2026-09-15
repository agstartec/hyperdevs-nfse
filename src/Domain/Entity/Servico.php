<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\Enum\TipoRetencaoIssqn;
use Hyperdevs\Nfse\Domain\Enum\TributacaoIssqn;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoMunicipio;
use Hyperdevs\Nfse\Domain\ValueObject\Money;

class Servico
{
    private Money $valorTotal;
    private Money $baseCalculo;
    private Money $valorIss;

    /**
     * @param DocDedRed[]|null $documentosDeducao
     */
    public function __construct(
        private string $discriminacao,
        private string $codigoTributacao,
        Money $valorServicos,
        private ?Money $descontoIncondicionado = null,
        private ?Money $descontoCondicionado = null,
        private ?float $aliquotaIss = null,
        private ?CodigoMunicipio $localPrestacao = null,
        private ?string $codigoNbs = null,
        private ?Obra $obra = null,
        private TributacaoIssqn $tribISSQN = TributacaoIssqn::OPERACAO_TRIBUTAVEL,
        private TipoRetencaoIssqn $tpRetISSQN = TipoRetencaoIssqn::NAO_RETIDO,
        private ?string $codigoPaisPrestacao = null,
        private ?string $codigoPaisResultado = null,
        private ?string $codigoTributacaoMunicipal = null,
        private ?string $codigoInternoContribuinte = null,
        private ?float $valorRecebido = null,
        private ?ComExterior $comExterior = null,
        private ?AtvEvento $atvEvento = null,
        private ?InfoCompl $infoCompl = null,
        private ?array $documentosDeducao = null,
        private ?float $percentualDeducao = null,
        private ?float $valorDeducaoPadrao = null,
        private ?int $tipoImunidade = null,
        private ?ExigSusp $exigSusp = null,
        private ?BeneficioMunicipal $beneficioMunicipal = null,
        private ?TribFederal $tribFederal = null,
        private ?string $totTribTipo = null,
        private ?float $pTotTribFed = null,
        private ?float $pTotTribEst = null,
        private ?float $pTotTribMun = null,
        private ?string $indTotTrib = null,
        private ?float $pTotTribSN = null,
        private ?float $vTotTribFed = null,
        private ?float $vTotTribEst = null,
        private ?float $vTotTribMun = null,
    ) {
        $this->calcularValores($valorServicos);
        $this->validate();
    }

    private function calcularValores(Money $valorServicos): void
    {
        $valorDeducoes = $this->calcularValorDeducao($valorServicos);
        $reducaoBM = $this->calcularReducaoBeneficioMunicipal($valorServicos);
        $descontoIncond = $this->descontoIncondicionado ?? new Money(0);
        $descontoCond = $this->descontoCondicionado ?? new Money(0);

        $this->baseCalculo = $valorServicos
            ->subtract($valorDeducoes)
            ->subtract($reducaoBM);
        $this->valorIss = $this->aliquotaIss !== null
            ? $this->baseCalculo->percentage($this->aliquotaIss)
            : new Money(0);
        $this->valorTotal = $valorServicos
            ->subtract($descontoIncond)
            ->subtract($descontoCond);
    }

    /**
     * Valor monetário da dedução/redução que reduz a base de cálculo do ISS,
     * derivado do grupo oficial <vDedRed> (choice: pDR | vDR | documentos).
     */
    private function calcularValorDeducao(Money $valorServicos): Money
    {
        if ($this->percentualDeducao !== null) {
            return $valorServicos->percentage($this->percentualDeducao);
        }

        if ($this->valorDeducaoPadrao !== null) {
            return new Money($this->valorDeducaoPadrao);
        }

        if ($this->documentosDeducao !== null) {
            $total = 0.0;
            foreach ($this->documentosDeducao as $doc) {
                $total += (float) $doc->getValorDeducao();
            }

            return new Money($total);
        }

        return new Money(0);
    }

    /**
     * Valor monetário da redução da base de cálculo por Benefício Municipal (BM),
     * derivado do choice vRedBCBM | pRedBCBM. Conforme o XSD:
     * vBC = vServ - descIncond - deduções - benefício municipal.
     */
    private function calcularReducaoBeneficioMunicipal(Money $valorServicos): Money
    {
        if ($this->beneficioMunicipal === null) {
            return new Money(0);
        }

        $valor = $this->beneficioMunicipal->getValorReducaoBC();
        if ($valor !== null) {
            return new Money($valor);
        }

        $percentual = $this->beneficioMunicipal->getPercentualReducaoBC();
        if ($percentual !== null) {
            return $valorServicos->percentage($percentual);
        }

        return new Money(0);
    }

    private function validate(): void
    {
        if (empty($this->discriminacao)) {
            throw new \InvalidArgumentException('Discriminação do serviço é obrigatória');
        }

        if (strlen($this->discriminacao) > 2000) {
            throw new \InvalidArgumentException('Discriminação deve ter no máximo 2000 caracteres');
        }

        if ($this->aliquotaIss !== null && ($this->aliquotaIss < 0 || $this->aliquotaIss > 100)) {
            throw new \InvalidArgumentException('Alíquota ISS deve estar entre 0 e 100');
        }

        // XSD TCLocPrest exige xs:choice minOccurs="1": exatamente um de cLocPrestacao ou cPaisPrestacao.
        if ($this->localPrestacao === null && $this->codigoPaisPrestacao === null) {
            throw new \InvalidArgumentException(
                'Local de prestação é obrigatório: informe cLocPrestacao (município IBGE) ou cPaisPrestacao (código ISO do país)'
            );
        }

        if (!$this->valorTotal->isPositive()) {
            throw new \InvalidArgumentException('Valor total deve ser positivo');
        }
    }

    public function getDiscriminacao(): string
    {
        return $this->discriminacao;
    }

    public function getCodigoTributacao(): string
    {
        return $this->codigoTributacao;
    }

    public function getLocalPrestacao(): ?CodigoMunicipio
    {
        return $this->localPrestacao;
    }

    public function getValorTotal(): Money
    {
        return $this->valorTotal;
    }

    public function getBaseCalculo(): Money
    {
        return $this->baseCalculo;
    }

    public function getValorIss(): Money
    {
        return $this->valorIss;
    }

    public function getDescontoIncondicionado(): ?Money
    {
        return $this->descontoIncondicionado;
    }

    public function getDescontoCondicionado(): ?Money
    {
        return $this->descontoCondicionado;
    }

    public function getAliquotaIss(): ?float
    {
        return $this->aliquotaIss;
    }

    public function getCodigoNbs(): ?string
    {
        return $this->codigoNbs;
    }

    public function getObra(): ?Obra
    {
        return $this->obra;
    }

    public function getTribISSQN(): TributacaoIssqn
    {
        return $this->tribISSQN;
    }

    public function getTpRetISSQN(): TipoRetencaoIssqn
    {
        return $this->tpRetISSQN;
    }

    public function getCodigoPaisPrestacao(): ?string
    {
        return $this->codigoPaisPrestacao;
    }

    public function getCodigoPaisResultado(): ?string
    {
        return $this->codigoPaisResultado;
    }

    public function getCodigoTributacaoMunicipal(): ?string
    {
        return $this->codigoTributacaoMunicipal;
    }

    public function getCodigoInternoContribuinte(): ?string
    {
        return $this->codigoInternoContribuinte;
    }

    public function getValorRecebido(): ?float
    {
        return $this->valorRecebido;
    }

    public function getComExterior(): ?ComExterior
    {
        return $this->comExterior;
    }

    public function getAtvEvento(): ?AtvEvento
    {
        return $this->atvEvento;
    }

    public function getInfoCompl(): ?InfoCompl
    {
        return $this->infoCompl;
    }

    /** @return DocDedRed[]|null */
    public function getDocumentosDeducao(): ?array
    {
        return $this->documentosDeducao;
    }

    public function getPercentualDeducao(): ?float
    {
        return $this->percentualDeducao;
    }

    public function getValorDeducaoPadrao(): ?float
    {
        return $this->valorDeducaoPadrao;
    }

    public function getTipoImunidade(): ?int
    {
        return $this->tipoImunidade;
    }

    public function getExigSusp(): ?ExigSusp
    {
        return $this->exigSusp;
    }

    public function getBeneficioMunicipal(): ?BeneficioMunicipal
    {
        return $this->beneficioMunicipal;
    }

    public function getTribFederal(): ?TribFederal
    {
        return $this->tribFederal;
    }

    public function getTotTribTipo(): ?string
    {
        return $this->totTribTipo;
    }

    public function getPTotTribFed(): ?float
    {
        return $this->pTotTribFed;
    }

    public function getPTotTribEst(): ?float
    {
        return $this->pTotTribEst;
    }

    public function getPTotTribMun(): ?float
    {
        return $this->pTotTribMun;
    }

    public function getIndTotTrib(): ?string
    {
        return $this->indTotTrib;
    }

    public function getPTotTribSN(): ?float
    {
        return $this->pTotTribSN;
    }

    public function getVTotTribFed(): ?float
    {
        return $this->vTotTribFed;
    }

    public function getVTotTribEst(): ?float
    {
        return $this->vTotTribEst;
    }

    public function getVTotTribMun(): ?float
    {
        return $this->vTotTribMun;
    }
}
