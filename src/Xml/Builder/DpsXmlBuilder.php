<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Xml\Builder;

use Hyperevs\Nfse\Domain\Entity\Dps;
use Hyperevs\Nfse\Domain\Entity\Endereco;
use Hyperevs\Nfse\Domain\Entity\IbsCbsDest;
use Hyperevs\Nfse\Domain\Entity\IbsCbsInfo;
use Hyperevs\Nfse\Domain\Entity\Intermediario;
use Hyperevs\Nfse\Domain\Entity\Prestador;
use Hyperevs\Nfse\Domain\Entity\Servico;
use Hyperevs\Nfse\Domain\Entity\Substituicao;
use Hyperevs\Nfse\Domain\Entity\Tomador;
use Hyperevs\Nfse\Domain\Enum\TipoEmitente;
use Hyperevs\Nfse\Domain\ValueObject\ChaveAcesso;
use NFePHP\Common\DOMImproved as Dom;

class DpsXmlBuilder implements Contract\XmlBuilderInterface
{
    private Dom $dom;

    public function __construct()
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    #[\Override]
    public function build(object $entity): string
    {
        if (!$entity instanceof Dps) {
            throw new \InvalidArgumentException('Entity must be an instance of Dps');
        }

        $this->reset();

        $dpsNode = $this->dom->createElement('DPS');
        $dpsNode->setAttribute('versao', $entity->getVersao()->value);
        $dpsNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infDpsNode = $this->dom->createElement('infDPS');
        $chaveAcesso = $entity->getChaveAcesso();
        if ($chaveAcesso === null) {
            throw new \InvalidArgumentException('DPS must have a chave de acesso');
        }
        $infDpsNode->setAttribute('Id', 'DPS' . substr($chaveAcesso->getChave(), 0, 42));

        $this->addChild($infDpsNode, 'tpAmb', $entity->getTipoAmbiente()->value);
        $this->addChild($infDpsNode, 'dhEmi', $entity->getDataEmissao()->format('Y-m-d\TH:i:sP'));
        $this->addChild($infDpsNode, 'verAplic', $entity->getVersaoAplicacao());
        $this->addChild($infDpsNode, 'serie', sprintf('%05d', $entity->getSerie()));
        $this->addChild($infDpsNode, 'nDPS', $entity->getNumero());
        $this->addChild($infDpsNode, 'dCompet', $entity->getDataCompetencia()->format('Y-m-d'));
        $this->addChild($infDpsNode, 'tpEmit', $entity->getTipoEmitente()->value);

        if ($entity->getCMotivoEmisTI() !== null) {
            $this->addChild($infDpsNode, 'cMotivoEmisTI', (string) $entity->getCMotivoEmisTI()->value);
        }

        if ($entity->getChNFSeRej() !== null) {
            $this->addChild($infDpsNode, 'chNFSeRej', $entity->getChNFSeRej()->getChave());
        }

        $this->addChild($infDpsNode, 'cLocEmi', $entity->getCodigoMunicipioEmissor()->getCodigo());

        if ($entity->getSubstituicao() !== null) {
            $this->buildSubstituicao($infDpsNode, $entity->getSubstituicao());
        }

        $prestadorEhEmitente = $entity->getTipoEmitente() === TipoEmitente::PRESTADOR;
        $this->buildPrestador($infDpsNode, $entity->getPrestador(), $prestadorEhEmitente);

        if ($entity->getTomador() !== null) {
            $this->buildPessoa($infDpsNode, $entity->getTomador(), 'toma');
        }

        if ($entity->getIntermediario() !== null) {
            $this->buildPessoa($infDpsNode, $entity->getIntermediario(), 'interm');
        }

        $this->buildServico($infDpsNode, $entity->getServico());

        $this->buildValores($infDpsNode, $entity->getServico());

        if ($entity->getIbsCbs() !== null) {
            $this->buildIbscbs($infDpsNode, $entity->getIbsCbs());
        }

        $dpsNode->appendChild($infDpsNode);
        $this->dom->appendChild($dpsNode);

        $xml = $this->dom->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Failed to generate XML');
        }

        return $xml;
    }

    private function reset(): void
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    private function addChild(\DOMNode $parent, string $name, mixed $value, bool $required = true): ?\DOMElement
    {
        if (!$required && ($value === null || $value === '')) {
            return null;
        }

        $element = $this->dom->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);

        return $element;
    }

    private function buildSubstituicao(\DOMNode $parent, Substituicao $substituicao): void
    {
        $substNode = $this->dom->createElement('subst');
        $parent->appendChild($substNode);

        $this->addChild($substNode, 'chSubstda', $substituicao->getChaveSubstituida()->getChave(), true);
        $this->addChild($substNode, 'cMotivo', $substituicao->getCodigoMotivo(), true);
        $this->addChild($substNode, 'xMotivo', $substituicao->getDescricaoMotivo(), false);
    }

    private function buildPrestador(\DOMNode $parent, Prestador $prestador, bool $prestadorEhEmitente): void
    {
        $prestNode = $this->dom->createElement('prest');
        $parent->appendChild($prestNode);

        // XSD TCInfoPrestador: choice CNPJ | CPF | NIF | cNaoNIF (exatamente um)
        if ($prestador->getCnpj()) {
            $this->addChild($prestNode, 'CNPJ', $prestador->getCnpj()->getNumero(), false);
        } elseif ($prestador->getCpf()) {
            $this->addChild($prestNode, 'CPF', $prestador->getCpf()->getNumero(), false);
        } elseif ($prestador->getNif()) {
            $this->addChild($prestNode, 'NIF', $prestador->getNif(), false);
        } elseif ($prestador->getCodigoNaoNif() !== null) {
            $this->addChild($prestNode, 'cNaoNIF', $prestador->getCodigoNaoNif(), false);
        }

        if ($prestador->getCaepf()) {
            $this->addChild($prestNode, 'CAEPF', $prestador->getCaepf(), false);
        }

        if ($prestador->getInscricaoMunicipal()) {
            $this->addChild($prestNode, 'IM', $prestador->getInscricaoMunicipal(), false);
        }


        if (!$prestadorEhEmitente) {
            $this->addChild($prestNode, 'xNome', $prestador->getRazaoSocial(), false);

            $endereco = $prestador->getEndereco();
            if ($endereco !== null) {
                $this->buildEndereco($prestNode, $endereco);
            }
        }

        if ($prestador->getTelefone()) {
            $this->addChild($prestNode, 'fone', $prestador->getTelefone()->getNumero(), false);
        }

        if ($prestador->getEmail()) {
            $this->addChild($prestNode, 'email', $prestador->getEmail()->getEmail(), false);
        }

        $regTrib = $this->dom->createElement('regTrib');
        $prestNode->appendChild($regTrib);
        $this->addChild($regTrib, 'opSimpNac', (string) $prestador->getRegimeTributario()->value, true);
        if ($prestador->getRegimeApuracaoSimplesNacional() !== null) {
            $this->addChild($regTrib, 'regApTribSN', (string) $prestador->getRegimeApuracaoSimplesNacional(), false);
        }
        $this->addChild($regTrib, 'regEspTrib', $prestador->getRegimeEspecialTributacao()->value, true);
    }

    private function buildPessoa(\DOMNode $parent, Tomador|Intermediario $pessoa, string $tagName): void
    {
        $node = $this->dom->createElement($tagName);
        $parent->appendChild($node);

        // XSD TCInfoPessoa: choice CNPJ | CPF | NIF | cNaoNIF (exatamente um)
        if ($pessoa->getNif() !== null) {
            $this->addChild($node, 'NIF', $pessoa->getNif(), false);
        } elseif ($pessoa->getCnpj() !== null) {
            $this->addChild($node, 'CNPJ', $pessoa->getCnpj()->getNumero(), false);
        } elseif ($pessoa->getCpf() !== null) {
            $this->addChild($node, 'CPF', $pessoa->getCpf()->getNumero(), false);
        } elseif ($pessoa->getCodigoNaoNif() !== null) {
            $this->addChild($node, 'cNaoNIF', $pessoa->getCodigoNaoNif(), false);
        }

        if ($pessoa->getCaepf() !== null) {
            $this->addChild($node, 'CAEPF', $pessoa->getCaepf(), false);
        }

        if ($pessoa->getInscricaoMunicipal() !== null) {
            $this->addChild($node, 'IM', $pessoa->getInscricaoMunicipal(), false);
        }

        $this->addChild($node, 'xNome', $pessoa->getRazaoSocial(), true);

        // <end> é opcional (TCInfoPessoa/end, minOccurs=0): omite quando não informado.
        if ($pessoa->getEndereco() !== null) {
            $this->buildEndereco($node, $pessoa->getEndereco());
        }

        if ($pessoa->getTelefone()) {
            $this->addChild($node, 'fone', $pessoa->getTelefone()->getNumero(), false);
        }

        if ($pessoa->getEmail()) {
            $this->addChild($node, 'email', $pessoa->getEmail()->getEmail(), false);
        }
    }

    private function buildEndereco(\DOMNode $parent, Endereco $endereco): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($endereco->isExterior()) {
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cPais', $endereco->getCodigoPais(), true);
            $this->addChild($extNode, 'cEndPost', $endereco->getCodigoPostalExterior(), true);
            $this->addChild($extNode, 'xCidade', $endereco->getNomeCidadeExterior(), true);
            $this->addChild($extNode, 'xEstProvReg', $endereco->getEstadoProvinciaExterior(), true);
        } else {
            $cMun = $endereco->getCodigoMunicipio();
            $cep = $endereco->getCep();
            $nacNode = $this->dom->createElement('endNac');
            $endNode->appendChild($nacNode);
            $this->addChild($nacNode, 'cMun', $cMun?->getCodigo(), true);
            $this->addChild($nacNode, 'CEP', $cep?->getCep(), true);
        }

        $this->addChild($endNode, 'xLgr', $endereco->getLogradouro(), true);
        $this->addChild($endNode, 'nro', $endereco->getNumero(), true);

        if ($endereco->getComplemento()) {
            $this->addChild($endNode, 'xCpl', $endereco->getComplemento(), false);
        }

        $this->addChild($endNode, 'xBairro', $endereco->getBairro(), true);
    }

    private function buildServico(\DOMNode $parent, Servico $servico): void
    {
        $servNode = $this->dom->createElement('serv');
        $parent->appendChild($servNode);

        $locPrest = $this->dom->createElement('locPrest');
        $servNode->appendChild($locPrest);
        if ($servico->getCodigoPaisPrestacao() !== null) {
            $this->addChild($locPrest, 'cPaisPrestacao', $servico->getCodigoPaisPrestacao(), true);
        } elseif ($servico->getLocalPrestacao() !== null) {
            $this->addChild($locPrest, 'cLocPrestacao', $servico->getLocalPrestacao()->getCodigo(), true);
        }

        $cServ = $this->dom->createElement('cServ');
        $servNode->appendChild($cServ);
        $this->addChild($cServ, 'cTribNac', $servico->getCodigoTributacao(), true);
        if ($servico->getCodigoTributacaoMunicipal() !== null) {
            $this->addChild($cServ, 'cTribMun', $servico->getCodigoTributacaoMunicipal(), false);
        }
        $this->addChild($cServ, 'xDescServ', $servico->getDiscriminacao(), true);
        if ($servico->getCodigoNbs()) {
            $this->addChild($cServ, 'cNBS', $servico->getCodigoNbs(), false);
        }
        if ($servico->getCodigoInternoContribuinte() !== null) {
            $this->addChild($cServ, 'cIntContrib', $servico->getCodigoInternoContribuinte(), false);
        }

        if ($servico->getComExterior() !== null) {
            $this->buildComExterior($servNode, $servico->getComExterior());
        }

        if ($servico->getObra() !== null) {
            $this->buildObra($servNode, $servico->getObra());
        }

        if ($servico->getAtvEvento() !== null) {
            $this->buildAtvEvento($servNode, $servico->getAtvEvento());
        }

        if ($servico->getInfoCompl() !== null) {
            $this->buildInfoCompl($servNode, $servico->getInfoCompl());
        }
    }

    private function buildComExterior(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\ComExterior $comExt): void
    {
        $node = $this->dom->createElement('comExt');
        $parent->appendChild($node);
        $this->addChild($node, 'mdPrestacao', (string) $comExt->getModoPrestacao(), true);
        $this->addChild($node, 'vincPrest', (string) $comExt->getVinculoPrestador(), true);
        $this->addChild($node, 'tpMoeda', $comExt->getCodigoMoeda(), true);
        $this->addChild($node, 'vServMoeda', number_format($comExt->getValorServicoMoeda(), 2, '.', ''), true);
        $this->addChild($node, 'mecAFComexP', $comExt->getMecanismoApoioPrestador(), true);
        $this->addChild($node, 'mecAFComexT', $comExt->getMecanismoApoioTomador(), true);
        $this->addChild($node, 'movTempBens', $comExt->getMovimentacaoTemporaria(), true);
        if ($comExt->getNumeroDeclaracaoImportacao() !== null) {
            $this->addChild($node, 'nDI', $comExt->getNumeroDeclaracaoImportacao(), false);
        }
        if ($comExt->getNumeroRegistroExportacao() !== null) {
            $this->addChild($node, 'nRE', $comExt->getNumeroRegistroExportacao(), false);
        }
        $this->addChild($node, 'mdic', $comExt->getEnviarMDIC(), true);
    }

    private function buildAtvEvento(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\AtvEvento $atv): void
    {
        $node = $this->dom->createElement('atvEvento');
        $parent->appendChild($node);
        $this->addChild($node, 'xNome', $atv->getDescricao(), true);
        $this->addChild($node, 'dtIni', $atv->getDataInicio()->format('Y-m-d'), true);
        $this->addChild($node, 'dtFim', $atv->getDataFim()->format('Y-m-d'), true);
        if ($atv->getIdentificacaoEvento() !== null) {
            $this->addChild($node, 'idAtvEvt', $atv->getIdentificacaoEvento(), true);
        } elseif ($atv->getEndereco() !== null) {
            $this->buildAtvEventoEndereco($node, $atv->getEndereco());
        }
    }

    private function buildAtvEventoEndereco(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsEnderecoObra $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($end->getCep() !== null) {
            $this->addChild($endNode, 'CEP', $end->getCep(), true);
        } elseif ($end->getEndExt() !== null) {
            $ext = $end->getEndExt();
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cEndPost', $ext->getCEndPost(), true);
            $this->addChild($extNode, 'xCidade', $ext->getXCidade(), true);
            $this->addChild($extNode, 'xEstProvReg', $ext->getXEstProvReg(), true);
        }

        $this->addChild($endNode, 'xLgr', $end->getXLgr(), true);
        $this->addChild($endNode, 'nro', $end->getNro(), true);
        $this->addChild($endNode, 'xCpl', $end->getXCpl(), false);
        $this->addChild($endNode, 'xBairro', $end->getXBairro(), true);
    }

    private function buildInfoCompl(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\InfoCompl $info): void
    {
        $node = $this->dom->createElement('infoCompl');
        $parent->appendChild($node);
        if ($info->getIdDocTecnico() !== null) {
            $this->addChild($node, 'idDocTec', $info->getIdDocTecnico(), false);
        }
        if ($info->getDocReferencia() !== null) {
            $this->addChild($node, 'docRef', $info->getDocReferencia(), false);
        }
        if ($info->getNumeroPedido() !== null) {
            $this->addChild($node, 'xPed', $info->getNumeroPedido(), false);
        }
        if ($info->getItensPedido() !== null) {
            $gItem = $this->dom->createElement('gItemPed');
            $node->appendChild($gItem);
            foreach ($info->getItensPedido() as $item) {
                $this->addChild($gItem, 'xItemPed', $item, true);
            }
        }
        if ($info->getInfoComplementar() !== null) {
            $this->addChild($node, 'xInfComp', $info->getInfoComplementar(), false);
        }
    }

    private function buildObra(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\Obra $obra): void
    {
        $node = $this->dom->createElement('obra');
        $parent->appendChild($node);

        $this->addChild($node, 'inscImobFisc', $obra->getInscImobFisc(), false);

        if ($obra->getCObra() !== null) {
            $this->addChild($node, 'cObra', $obra->getCObra(), true);
        } elseif ($obra->getCCIB() !== null) {
            $this->addChild($node, 'cCIB', $obra->getCCIB()->getCodigo(), true);
        } elseif ($obra->getEndereco() !== null) {
            $this->buildObraEndereco($node, $obra->getEndereco());
        }
    }

    private function buildObraEndereco(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsEnderecoObra $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($end->getCEp() !== null) {
            $this->addChild($endNode, 'CEP', $end->getCEp(), true);
        } elseif ($end->getEndExt() !== null) {
            $ext = $end->getEndExt();
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cEndPost', $ext->getCEndPost(), true);
            $this->addChild($extNode, 'xCidade', $ext->getXCidade(), true);
            $this->addChild($extNode, 'xEstProvReg', $ext->getXEstProvReg(), true);
        }

        $this->addChild($endNode, 'xLgr', $end->getXLgr(), true);
        $this->addChild($endNode, 'nro', $end->getNro(), true);
        $this->addChild($endNode, 'xCpl', $end->getXCpl(), false);
        $this->addChild($endNode, 'xBairro', $end->getXBairro(), true);
    }

    private function buildValores(\DOMNode $parent, Servico $servico): void
    {
        $valNode = $this->dom->createElement('valores');
        $parent->appendChild($valNode);

        $vServPrest = $this->dom->createElement('vServPrest');
        $valNode->appendChild($vServPrest);
        if ($servico->getValorRecebido() !== null) {
            $this->addChild($vServPrest, 'vReceb', number_format($servico->getValorRecebido(), 2, '.', ''), false);
        }
        $this->addChild($vServPrest, 'vServ', number_format($servico->getValorTotal()->getValue(), 2, '.', ''), true);

        $descIncond = $servico->getDescontoIncondicionado();
        $descCond = $servico->getDescontoCondicionado();
        if ($descIncond !== null && $descIncond->isPositive() || $descCond !== null && $descCond->isPositive()) {
            $desc = $this->dom->createElement('vDescCondIncond');
            $valNode->appendChild($desc);
            $this->addChild($desc, 'vDescIncond', $descIncond !== null ? number_format($descIncond->getValue(), 2, '.', '') : '0.00', false);
            $this->addChild($desc, 'vDescCond', $descCond !== null ? number_format($descCond->getValue(), 2, '.', '') : '0.00', false);
        }

        if ($servico->getPercentualDeducao() !== null) {
            $vDedRed = $this->dom->createElement('vDedRed');
            $valNode->appendChild($vDedRed);
            $this->addChild($vDedRed, 'pDR', number_format($servico->getPercentualDeducao(), 2, '.', ''), true);
        } elseif ($servico->getValorDeducaoPadrao() !== null) {
            $vDedRed = $this->dom->createElement('vDedRed');
            $valNode->appendChild($vDedRed);
            $this->addChild($vDedRed, 'vDR', number_format($servico->getValorDeducaoPadrao(), 2, '.', ''), true);
        } elseif ($servico->getDocumentosDeducao() !== null) {
            $this->buildDedRed($valNode, $servico->getDocumentosDeducao());
        }

        $tribNode = $this->dom->createElement('trib');
        $valNode->appendChild($tribNode);

        $tribMun = $this->dom->createElement('tribMun');
        $tribNode->appendChild($tribMun);
        $this->addChild($tribMun, 'tribISSQN', $servico->getTribISSQN()->value, true);
        if ($servico->getCodigoPaisResultado() !== null) {
            $this->addChild($tribMun, 'cPaisResult', $servico->getCodigoPaisResultado(), false);
        }
        if ($servico->getTipoImunidade() !== null) {
            $this->addChild($tribMun, 'tpImunidade', (string) $servico->getTipoImunidade(), false);
        }
        if ($servico->getExigSusp() !== null) {
            $this->buildExigSusp($tribMun, $servico->getExigSusp());
        }
        if ($servico->getBeneficioMunicipal() !== null) {
            $bmNode = $this->dom->createElement('BM');
            $tribMun->appendChild($bmNode);
            $this->addChild($bmNode, 'nBM', $servico->getBeneficioMunicipal()->getNumeroBeneficio(), true);
            if ($servico->getBeneficioMunicipal()->getValorReducaoBC() !== null) {
                $this->addChild($bmNode, 'vRedBCBM', number_format($servico->getBeneficioMunicipal()->getValorReducaoBC(), 2, '.', ''), false);
            } elseif ($servico->getBeneficioMunicipal()->getPercentualReducaoBC() !== null) {
                $this->addChild($bmNode, 'pRedBCBM', number_format($servico->getBeneficioMunicipal()->getPercentualReducaoBC(), 2, '.', ''), false);
            }
        }
        $this->addChild($tribMun, 'tpRetISSQN', $servico->getTpRetISSQN()->value, true);
        if ($servico->getAliquotaIss() !== null) {
            $this->addChild($tribMun, 'pAliq', number_format($servico->getAliquotaIss(), 2, '.', ''), false);
        }

        if ($servico->getTribFederal() !== null) {
            $this->buildTribFederal($tribNode, $servico->getTribFederal());
        }

        $this->buildTotTrib($tribNode, $servico);
    }

    private function buildExigSusp(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\ExigSusp $exigSusp): void
    {
        $node = $this->dom->createElement('exigSusp');
        $parent->appendChild($node);
        $this->addChild($node, 'tpSusp', (string) $exigSusp->getTipoSuspensao(), true);
        $this->addChild($node, 'nProcesso', $exigSusp->getNumeroProcesso(), true);
    }

    private function buildTribFederal(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\TribFederal $tribFed): void
    {
        $node = $this->dom->createElement('tribFed');
        $parent->appendChild($node);

        if ($tribFed->getPisCofinsCst() !== null || $tribFed->getPisCofinsAliquotaPis() !== null) {
            $cst = $tribFed->getPisCofinsCst();
            if ($cst === null) {
                throw new \InvalidArgumentException(
                    'CST do PIS/COFINS é obrigatório quando o grupo piscofins é emitido (TCTribOutrosPisCofins)'
                );
            }
            $pisNode = $this->dom->createElement('piscofins');
            $node->appendChild($pisNode);
            $this->addChild($pisNode, 'CST', $cst, true);
            if ($tribFed->getPisCofinsBaseCalculo() !== null) {
                $this->addChild($pisNode, 'vBCPisCofins', number_format((float) $tribFed->getPisCofinsBaseCalculo(), 2, '.', ''), false);
            }
            if ($tribFed->getPisCofinsAliquotaPis() !== null) {
                $this->addChild($pisNode, 'pAliqPis', number_format($tribFed->getPisCofinsAliquotaPis(), 2, '.', ''), false);
            }
            if ($tribFed->getPisCofinsAliquotaCofins() !== null) {
                $this->addChild($pisNode, 'pAliqCofins', number_format($tribFed->getPisCofinsAliquotaCofins(), 2, '.', ''), false);
            }
            if ($tribFed->getValorPis() !== null) {
                $this->addChild($pisNode, 'vPis', number_format((float) $tribFed->getValorPis(), 2, '.', ''), false);
            }
            if ($tribFed->getValorCofins() !== null) {
                $this->addChild($pisNode, 'vCofins', number_format((float) $tribFed->getValorCofins(), 2, '.', ''), false);
            }
            if ($tribFed->getPisCofinsTipo() !== null) {
                $this->addChild($pisNode, 'tpRetPisCofins', $tribFed->getPisCofinsTipo(), true);
            }
        }

        if ($tribFed->getValorRetidoCP() !== null) {
            $this->addChild($node, 'vRetCP', $tribFed->getValorRetidoCP(), false);
        }
        if ($tribFed->getValorRetidoIRRF() !== null) {
            $this->addChild($node, 'vRetIRRF', $tribFed->getValorRetidoIRRF(), false);
        }
        if ($tribFed->getValorRetidoCSLL() !== null) {
            $this->addChild($node, 'vRetCSLL', $tribFed->getValorRetidoCSLL(), false);
        }
    }

    /** @param \Hyperevs\Nfse\Domain\Entity\DocDedRed[] $docs */
    private function buildDedRed(\DOMNode $parent, array $docs): void
    {
        $vDedRed = $this->dom->createElement('vDedRed');
        $parent->appendChild($vDedRed);

        $docListNode = $this->dom->createElement('documentos');
        $vDedRed->appendChild($docListNode);

        foreach ($docs as $doc) {
            $docNode = $this->dom->createElement('docDedRed');
            $docListNode->appendChild($docNode);

            match ($doc->getTipoDocumento()) {
                'chNFSe' => $this->addChild($docNode, 'chNFSe', $doc->getChaveNFSe() ?? '', true),
                'chNFe' => $this->addChild($docNode, 'chNFe', $doc->getChaveNFe() ?? '', true),
                'NFSeMun' => $this->buildDocNFSeMun($docNode, $doc),
                'NFNFS' => $this->buildDocNFNFS($docNode, $doc),
                'nDocFisc' => $this->addChild($docNode, 'nDocFisc', $doc->getNumeroDocFiscal() ?? '', true),
                default => $this->addChild($docNode, 'nDoc', $doc->getNumeroDoc() ?? '', true),
            };

            $this->addChild($docNode, 'tpDedRed', $doc->getTipoDeducaoReducao(), true);
            if ($doc->getDescricaoOutrasDeducoes() !== null) {
                $this->addChild($docNode, 'xDescOutDed', $doc->getDescricaoOutrasDeducoes(), false);
            }
            $this->addChild($docNode, 'dtEmiDoc', $doc->getDataEmissaoDoc()->format('Y-m-d'), true);
            $this->addChild($docNode, 'vDedutivelRedutivel', $doc->getValorDedutivel(), true);
            $this->addChild($docNode, 'vDeducaoReducao', $doc->getValorDeducao(), true);

            if ($doc->getFornecedor() !== null) {
                $fornec = $doc->getFornecedor();
                $fNode = $this->dom->createElement('fornec');
                $docNode->appendChild($fNode);
                if ($fornec->getCnpj()) {
                    $this->addChild($fNode, 'CNPJ', $fornec->getCnpj()->getNumero(), true);
                } elseif ($fornec->getCpf()) {
                    $this->addChild($fNode, 'CPF', $fornec->getCpf()->getNumero(), true);
                } elseif ($fornec->getNif()) {
                    $this->addChild($fNode, 'NIF', $fornec->getNif()->getNif(), true);
                } else {
                    $this->addChild($fNode, 'cNaoNIF', $fornec->getCodigoNaoNif(), true);
                }
                $this->addChild($fNode, 'xNome', $fornec->getXNome(), true);
            }
        }
    }

    private function buildDocNFSeMun(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\DocDedRed $doc): void
    {
        $node = $this->dom->createElement('NFSeMun');
        $parent->appendChild($node);
        $this->addChild($node, 'cMunNFSeMun', $doc->getCodigoMunicipioNFSe() ?? '', true);
        $this->addChild($node, 'nNFSeMun', $doc->getNumeroNFSe() ?? '', true);
        $this->addChild($node, 'cVerifNFSeMun', $doc->getCodigoVerificacaoNFSe() ?? '', true);
    }

    private function buildDocNFNFS(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\DocDedRed $doc): void
    {
        $node = $this->dom->createElement('NFNFS');
        $parent->appendChild($node);
        $this->addChild($node, 'nNFS', $doc->getNumeroNFS() ?? '', true);
        $this->addChild($node, 'modNFS', $doc->getModeloNFS() ?? '', true);
        $this->addChild($node, 'serieNFS', $doc->getSerieNFS() ?? '', true);
    }

    private function buildTotTrib(\DOMNode $parent, Servico $servico): void
    {
        $tt = $this->dom->createElement('totTrib');
        $parent->appendChild($tt);

        $tipo = $servico->getTotTribTipo();

        if ($tipo === 'pTotTribSN') {
            $sn = $this->requireFloat($servico->getPTotTribSN(), 'pTotTribSN');
            $this->addChild($tt, 'pTotTribSN', number_format($sn, 2, '.', ''), true);
        } elseif ($tipo === 'indTotTrib') {
            $ind = $servico->getIndTotTrib();
            if ($ind === null) {
                throw $this->faltaCampo('indTotTrib');
            }
            $this->addChild($tt, 'indTotTrib', $ind, true);
        } elseif ($tipo === 'pTotTrib') {
            $fed = $this->requireFloat($servico->getPTotTribFed(), 'pTotTribFed');
            $est = $this->requireFloat($servico->getPTotTribEst(), 'pTotTribEst');
            $mun = $this->requireFloat($servico->getPTotTribMun(), 'pTotTribMun');
            $pt = $this->dom->createElement('pTotTrib');
            $tt->appendChild($pt);
            $this->addChild($pt, 'pTotTribFed', number_format($fed, 2, '.', ''), true);
            $this->addChild($pt, 'pTotTribEst', number_format($est, 2, '.', ''), true);
            $this->addChild($pt, 'pTotTribMun', number_format($mun, 2, '.', ''), true);
        } elseif ($tipo === 'vTotTrib') {
            $fed = $this->requireFloat($servico->getVTotTribFed(), 'vTotTribFed');
            $est = $this->requireFloat($servico->getVTotTribEst(), 'vTotTribEst');
            $mun = $this->requireFloat($servico->getVTotTribMun(), 'vTotTribMun');
            $vt = $this->dom->createElement('vTotTrib');
            $tt->appendChild($vt);
            $this->addChild($vt, 'vTotTribFed', number_format($fed, 2, '.', ''), true);
            $this->addChild($vt, 'vTotTribEst', number_format($est, 2, '.', ''), true);
            $this->addChild($vt, 'vTotTribMun', number_format($mun, 2, '.', ''), true);
        } else {
            throw new \InvalidArgumentException(
                'totTribTipo é obrigatório e deve ser um de: vTotTrib, pTotTrib, indTotTrib, pTotTribSN — a biblioteca não presume valores de total de tributos'
            );
        }
    }

    private function requireFloat(?float $value, string $campo): float
    {
        if ($value === null) {
            throw $this->faltaCampo($campo);
        }

        return $value;
    }

    private function faltaCampo(string $campo): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            "{$campo} é obrigatório para o modo de total de tributos informado — a biblioteca não presume valores fiscais"
        );
    }

    private function buildIbscbs(\DOMNode $parent, IbsCbsInfo $ibscbs): void
    {
        $node = $this->dom->createElement('IBSCBS');
        $parent->appendChild($node);

        $this->addChild($node, 'finNFSe', $ibscbs->getFinNFSe()->value, true);
        $this->addChild($node, 'indFinal', $ibscbs->getIndFinal()?->value, false);
        $this->addChild($node, 'cIndOp', $ibscbs->getCIndOp()->getCodigo(), true);
        $this->addChild($node, 'tpOper', $ibscbs->getTpOper()?->value, false);

        if ($ibscbs->hasRefNFSe()) {
            $this->buildGRefNFSe($node, $ibscbs->getRefNFSeList());
        }

        $this->addChild($node, 'tpEnteGov', $ibscbs->getTpEnteGov()?->value, false);
        $this->addChild($node, 'indDest', $ibscbs->getIndDest()->value, true);

        if ($ibscbs->getDest() !== null) {
            $this->buildIbscbsDest($node, $ibscbs->getDest());
        }

        if ($ibscbs->getImovel() !== null) {
            $this->buildIbscbsImovel($node, $ibscbs->getImovel());
        }

        $this->buildIbscbsValores($node, $ibscbs);
    }

    /**
     * @param ChaveAcesso[]|null $refList
     */
    private function buildGRefNFSe(\DOMNode $parent, ?array $refList): void
    {
        if ($refList === null) {
            return;
        }

        $gRefNode = $this->dom->createElement('gRefNFSe');
        $parent->appendChild($gRefNode);

        foreach ($refList as $ref) {
            $this->addChild($gRefNode, 'refNFSe', $ref->getChave(), true);
        }
    }

    private function buildIbscbsDest(\DOMNode $parent, IbsCbsDest $dest): void
    {
        $destNode = $this->dom->createElement('dest');
        $parent->appendChild($destNode);

        // XSD TCRTCInfoDest: choice CNPJ | CPF | NIF | cNaoNIF (exatamente um).
        if ($dest->getCnpj()) {
            $this->addChild($destNode, 'CNPJ', $dest->getCnpj()->getNumero(), false);
        } elseif ($dest->getCpf()) {
            $this->addChild($destNode, 'CPF', $dest->getCpf()->getNumero(), false);
        } elseif ($dest->getNif()) {
            $this->addChild($destNode, 'NIF', $dest->getNif(), false);
        } elseif ($dest->getCodigoNaoNif() !== null) {
            $this->addChild($destNode, 'cNaoNIF', $dest->getCodigoNaoNif(), false);
        }

        $this->addChild($destNode, 'xNome', $dest->getXNome(), true);

        if ($dest->getEndereco() !== null) {
            $this->buildEndereco($destNode, $dest->getEndereco());
        }

        $this->addChild($destNode, 'fone', $dest->getFone(), false);
        $this->addChild($destNode, 'email', $dest->getEmail(), false);
    }

    private function buildIbscbsImovel(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsImovel $imovel): void
    {
        $node = $this->dom->createElement('imovel');
        $parent->appendChild($node);

        $this->addChild($node, 'inscImobFisc', $imovel->getInscImobFisc(), false);

        if ($imovel->getCCIB() !== null) {
            $this->addChild($node, 'cCIB', $imovel->getCCIB()->getCodigo(), true);
        } elseif ($imovel->getEndereco() !== null) {
            $this->buildIbscbsEnderecoObra($node, $imovel->getEndereco());
        }

        if ($imovel->getCCIB() !== null && $imovel->getEndereco() !== null) {
            trigger_error('Ambos cCIB e endereco informados em imovel — apenas um deve ser usado (XSD choice)', E_USER_WARNING);
        }
    }

    private function buildIbscbsEnderecoObra(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsEnderecoObra $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($end->getCEp() !== null) {
            $this->addChild($endNode, 'CEP', $end->getCEp(), true);
        } elseif ($end->getEndExt() !== null) {
            $ext = $end->getEndExt();
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cEndPost', $ext->getCEndPost(), true);
            $this->addChild($extNode, 'xCidade', $ext->getXCidade(), true);
            $this->addChild($extNode, 'xEstProvReg', $ext->getXEstProvReg(), true);
        }

        $this->addChild($endNode, 'xLgr', $end->getXLgr(), true);
        $this->addChild($endNode, 'nro', $end->getNro(), true);
        $this->addChild($endNode, 'xCpl', $end->getXCpl(), false);
        $this->addChild($endNode, 'xBairro', $end->getXBairro(), true);
    }

    private function buildIbscbsValores(\DOMNode $parent, IbsCbsInfo $ibscbs): void
    {
        $valNode = $this->dom->createElement('valores');
        $parent->appendChild($valNode);

        if ($ibscbs->getReeRepRes() !== null) {
            $this->buildGReeRepRes($valNode, $ibscbs->getReeRepRes());
        }

        $tribNode = $this->dom->createElement('trib');
        $valNode->appendChild($tribNode);

        $gIbscbs = $this->dom->createElement('gIBSCBS');
        $tribNode->appendChild($gIbscbs);
        $this->addChild($gIbscbs, 'CST', $ibscbs->getCst()->getCodigo(), true);
        $this->addChild($gIbscbs, 'cClassTrib', $ibscbs->getCClassTrib()->getCodigo(), true);
        $this->addChild($gIbscbs, 'cCredPres', $ibscbs->getCCredPres()?->getCodigo(), false);

        if ($ibscbs->getTribRegular() !== null) {
            $gReg = $this->dom->createElement('gTribRegular');
            $gIbscbs->appendChild($gReg);
            $this->addChild($gReg, 'CSTReg', $ibscbs->getTribRegular()->getCstReg()->getCodigo(), true);
            $this->addChild($gReg, 'cClassTribReg', $ibscbs->getTribRegular()->getCClassTribReg()->getCodigo(), true);
        }

        if ($ibscbs->getDiferimento() !== null) {
            $gDif = $this->dom->createElement('gDif');
            $gIbscbs->appendChild($gDif);
            $this->addChild($gDif, 'pDifUF', number_format($ibscbs->getDiferimento()->getPDifUF(), 2, '.', ''), true);
            $this->addChild($gDif, 'pDifMun', number_format($ibscbs->getDiferimento()->getPDifMun(), 2, '.', ''), true);
            $this->addChild($gDif, 'pDifCBS', number_format($ibscbs->getDiferimento()->getPDifCBS(), 2, '.', ''), true);
        }
    }

    private function buildGReeRepRes(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsReeRepRes $reeRepRes): void
    {
        $gNode = $this->dom->createElement('gReeRepRes');
        $parent->appendChild($gNode);

        foreach ($reeRepRes->getDocumentos() as $doc) {
            $docNode = $this->dom->createElement('documentos');
            $gNode->appendChild($docNode);

            match ($doc->getTipo()) {
                'dFeNacional' => $this->buildDocDFeNacional($docNode, $doc),
                'docFiscalOutro' => $this->buildDocFiscalOutro($docNode, $doc),
                'docOutro' => $this->buildDocOutro($docNode, $doc),
                default => throw new \InvalidArgumentException('Tipo de documento inválido: ' . $doc->getTipo()),
            };

            if ($doc->getFornec() !== null) {
                $this->buildDocFornec($docNode, $doc->getFornec());
            }

            $this->addChild($docNode, 'dtEmiDoc', $doc->getDtEmiDoc()->format('Y-m-d'), true);
            $this->addChild($docNode, 'dtCompDoc', $doc->getDtCompDoc()->format('Y-m-d'), true);
            $this->addChild($docNode, 'tpReeRepRes', $doc->getTpReeRepRes()->value, true);
            $this->addChild($docNode, 'xTpReeRepRes', $doc->getXTpReeRepRes(), false);
            $this->addChild($docNode, 'vlrReeRepRes', $doc->getVlrReeRepRes(), true);
        }
    }

    private function buildDocDFeNacional(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('dFeNacional');
        $parent->appendChild($node);
        $this->addChild($node, 'tipoChaveDFe', $doc->getTipoChaveDFe(), true);
        $this->addChild($node, 'xTipoChaveDFe', $doc->getXTipoChaveDFe(), false);
        $this->addChild($node, 'chaveDFe', $doc->getChaveDFe(), true);
    }

    private function buildDocFiscalOutro(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('docFiscalOutro');
        $parent->appendChild($node);
        $this->addChild($node, 'cMunDocFiscal', $doc->getCMunDocFiscal(), true);
        $this->addChild($node, 'nDocFiscal', $doc->getNDocFiscal(), true);
        $this->addChild($node, 'xDocFiscal', $doc->getXDocFiscal(), true);
    }

    private function buildDocOutro(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('docOutro');
        $parent->appendChild($node);
        $this->addChild($node, 'nDoc', $doc->getNDoc(), true);
        $this->addChild($node, 'xDoc', $doc->getXDoc(), true);
    }

    private function buildDocFornec(\DOMNode $parent, \Hyperevs\Nfse\Domain\Entity\IbsCbsFornecedor $fornec): void
    {
        $node = $this->dom->createElement('fornec');
        $parent->appendChild($node);

        if ($fornec->getCnpj()) {
            $this->addChild($node, 'CNPJ', $fornec->getCnpj()->getNumero(), true);
        } elseif ($fornec->getCpf()) {
            $this->addChild($node, 'CPF', $fornec->getCpf()->getNumero(), true);
        } elseif ($fornec->getNif()) {
            $this->addChild($node, 'NIF', $fornec->getNif()->getNif(), true);
        } else {
            $this->addChild($node, 'cNaoNIF', $fornec->getCodigoNaoNif(), true);
        }

        $this->addChild($node, 'xNome', $fornec->getXNome(), true);
    }
}
