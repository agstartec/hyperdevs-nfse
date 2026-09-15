<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Xml\Parser;

class NfseXmlParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $xml): array
    {
        if (empty(trim($xml))) {
            return [];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // LIBXML_NONET bloqueia resolução externa (mitiga XXE/SSRF); NOENT expandiria entidades e foi removido de propósito.
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        if (!$loaded) {
            return [];
        }

        $data = [];

        foreach ($dom->getElementsByTagName('NFSe') as $nfse) {
            $infNfse = $this->getDirectChild($nfse, 'infNFSe');
            if ($infNfse === null) {
                continue;
            }

            $versaoNfse = $nfse->getAttribute('versao') ?: null;
            $data[] = $this->parseInfNfse($infNfse, $versaoNfse);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseInfNfse(\DOMElement $infNfse, ?string $versaoNfse): array
    {
        $emitNode    = $this->getDirectChild($infNfse, 'emit');
        $valoresNode = $this->getDirectChild($infNfse, 'valores');
        $dpsNode     = $this->getDirectChild($infNfse, 'DPS');
        $infDpsNode  = $dpsNode?->getElementsByTagName('infDPS')->item(0);
        $versaoDps   = $dpsNode?->getAttribute('versao') ?: null;

        return [
            // Identificação da NFS-e (TCNFSe + TCInfNFSe)
            'versao'          => $versaoNfse,
            'id'              => $infNfse->getAttribute('Id'),
            'xLocEmi'         => $this->getDirectChildValue($infNfse, 'xLocEmi'),
            'xLocPrestacao'   => $this->getDirectChildValue($infNfse, 'xLocPrestacao'),
            'numero'          => $this->getDirectChildValue($infNfse, 'nNFSe'),
            'cLocIncid'       => $this->getDirectChildValue($infNfse, 'cLocIncid'),
            'xLocIncid'       => $this->getDirectChildValue($infNfse, 'xLocIncid'),
            'xTribNac'        => $this->getDirectChildValue($infNfse, 'xTribNac'),
            'xTribMun'        => $this->getDirectChildValue($infNfse, 'xTribMun'),
            'xNBS'            => $this->getDirectChildValue($infNfse, 'xNBS'),
            'verAplic'        => $this->getDirectChildValue($infNfse, 'verAplic'),
            'ambienteGerador' => $this->getDirectChildValue($infNfse, 'ambGer'),
            'tpEmis'          => $this->getDirectChildValue($infNfse, 'tpEmis'),
            'procEmi'         => $this->getDirectChildValue($infNfse, 'procEmi'),
            'codigoStatus'    => $this->getDirectChildValue($infNfse, 'cStat'),
            'dataHoraEmissaoNfse' => $this->getDirectChildValue($infNfse, 'dhProc'),
            'nDFSe'           => $this->getDirectChildValue($infNfse, 'nDFSe'),
            'competencia'     => $infDpsNode !== null ? $this->getNodeValue($infDpsNode, 'dCompet') : null,
            'xOutInf'         => $this->getDirectChildValue($infNfse, 'xOutInf'),

            // Emitente
            'emit'    => $emitNode !== null ? $this->parseEmit($emitNode) : null,

            // Valores da NFS-e (TCValoresNFSe — filho direto, não o valores do DPS)
            'valores' => $valoresNode !== null ? $this->parseValoresNfse($valoresNode) : null,

            // DPS embutida
            'dps'     => $infDpsNode !== null ? $this->parseDps($infDpsNode, $versaoDps) : null,

            // IBS/CBS (filho direto de infNFSe — já protegido em parseIbscbs)
            'ibscbs'  => $this->parseIbscbs($infNfse),
        ];
    }

    private function getDirectChild(\DOMElement $parent, string $localName): ?\DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $localName) {
                return $child;
            }
        }
        return null;
    }

    private function getDirectChildValue(\DOMElement $parent, string $localName): ?string
    {
        $child = $this->getDirectChild($parent, $localName);
        return $child !== null ? trim($child->textContent) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseEmit(\DOMElement $emit): array
    {
        $enderNode = $emit->getElementsByTagName('enderNac')->item(0);

        return [
            'cnpj'  => $this->getNodeValue($emit, 'CNPJ'),
            'cpf'   => $this->getNodeValue($emit, 'CPF'),
            'im'    => $this->getNodeValue($emit, 'IM'),
            'xNome' => $this->getNodeValue($emit, 'xNome'),
            'xFant' => $this->getNodeValue($emit, 'xFant'),
            'fone'  => $this->getNodeValue($emit, 'fone'),
            'email' => $this->getNodeValue($emit, 'email'),
            'endereco' => $enderNode !== null ? [
                'xLgr'   => $this->getNodeValue($enderNode, 'xLgr'),
                'nro'    => $this->getNodeValue($enderNode, 'nro'),
                'xCpl'   => $this->getNodeValue($enderNode, 'xCpl'),
                'xBairro' => $this->getNodeValue($enderNode, 'xBairro'),
                'cMun'   => $this->getNodeValue($enderNode, 'cMun'),
                'uf'     => $this->getNodeValue($enderNode, 'UF'),
                'cep'    => $this->getNodeValue($enderNode, 'CEP'),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseValoresNfse(\DOMElement $valores): array
    {
        return [
            'vCalcDR'   => $this->getNodeValue($valores, 'vCalcDR'),
            'tpBM'      => $this->getNodeValue($valores, 'tpBM'),
            'vCalcBM'   => $this->getNodeValue($valores, 'vCalcBM'),
            'vBC'       => $this->getNodeValue($valores, 'vBC'),
            'pAliqAplic' => $this->getNodeValue($valores, 'pAliqAplic'),
            'vISSQN'    => $this->getNodeValue($valores, 'vISSQN'),
            'vTotalRet' => $this->getNodeValue($valores, 'vTotalRet'),
            'vLiq'      => $this->getNodeValue($valores, 'vLiq'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDps(\DOMElement $infDps, ?string $versaoDps): array
    {
        $prestNode   = $infDps->getElementsByTagName('prest')->item(0);
        $tomaNode    = $infDps->getElementsByTagName('toma')->item(0);
        $intermNode  = $infDps->getElementsByTagName('interm')->item(0);
        $servNode    = $infDps->getElementsByTagName('serv')->item(0);
        $valoresNode = $infDps->getElementsByTagName('valores')->item(0);
        $substNode   = $infDps->getElementsByTagName('subst')->item(0);

        return [
            'versao'         => $versaoDps,
            'id'             => $infDps->getAttribute('Id'),
            'tpAmb'          => $this->getNodeValue($infDps, 'tpAmb'),
            'dhEmi'          => $this->getNodeValue($infDps, 'dhEmi'),
            'verAplic'       => $this->getNodeValue($infDps, 'verAplic'),
            'serie'          => $this->getNodeValue($infDps, 'serie'),
            'nDPS'           => $this->getNodeValue($infDps, 'nDPS'),
            'dCompet'        => $this->getNodeValue($infDps, 'dCompet'),
            'tpEmit'         => $this->getNodeValue($infDps, 'tpEmit'),
            'cMotivoEmisTI'  => $this->getNodeValue($infDps, 'cMotivoEmisTI'),
            'chNFSeRej'      => $this->getNodeValue($infDps, 'chNFSeRej'),
            'cLocEmi'        => $this->getNodeValue($infDps, 'cLocEmi'),
            'prestador'      => $prestNode !== null ? $this->parsePrestador($prestNode) : null,
            'tomador'        => $tomaNode !== null ? $this->parseParte($tomaNode) : null,
            'intermediario'  => $intermNode !== null ? $this->parseParte($intermNode) : null,
            'servico'        => $servNode !== null ? $this->parseServico($servNode) : null,
            'valores'        => $valoresNode !== null ? $this->parseValoresDps($valoresNode) : null,
            'substituicao'   => $substNode !== null ? [
                'chSubstda' => $this->getNodeValue($substNode, 'chSubstda'),
                'cMotivo'   => $this->getNodeValue($substNode, 'cMotivo'),
                'xMotivo'   => $this->getNodeValue($substNode, 'xMotivo'),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePrestador(\DOMElement $prest): array
    {
        $regTribNode = $prest->getElementsByTagName('regTrib')->item(0);
        $endNode     = $prest->getElementsByTagName('end')->item(0);

        return [
            'cnpj'     => $this->getNodeValue($prest, 'CNPJ'),
            'cpf'      => $this->getNodeValue($prest, 'CPF'),
            'nif'      => $this->getNodeValue($prest, 'NIF'),
            'cNaoNIF'  => $this->getNodeValue($prest, 'cNaoNIF'),
            'caepf'    => $this->getNodeValue($prest, 'CAEPF'),
            'im'       => $this->getNodeValue($prest, 'IM'),
            'xNome'    => $this->getNodeValue($prest, 'xNome'),
            'fone'     => $this->getNodeValue($prest, 'fone'),
            'email'    => $this->getNodeValue($prest, 'email'),
            'endereco' => $endNode !== null ? $this->parseEndereco($endNode) : null,
            'regTrib'  => $regTribNode !== null ? [
                'opSimpNac'   => $this->getNodeValue($regTribNode, 'opSimpNac'),
                'regApTribSN' => $this->getNodeValue($regTribNode, 'regApTribSN'),
                'regEspTrib'  => $this->getNodeValue($regTribNode, 'regEspTrib'),
            ] : null,
        ];
    }

    /**
     * TCInfoPessoa — tomador e intermediário (ambos têm IM pelo XSD).
     * @return array<string, mixed>
     */
    private function parseParte(\DOMElement $node): array
    {
        $endNode = $node->getElementsByTagName('end')->item(0);

        return [
            'cnpj'     => $this->getNodeValue($node, 'CNPJ'),
            'cpf'      => $this->getNodeValue($node, 'CPF'),
            'nif'      => $this->getNodeValue($node, 'NIF'),
            'cNaoNIF'  => $this->getNodeValue($node, 'cNaoNIF'),
            'caepf'    => $this->getNodeValue($node, 'CAEPF'),
            'im'       => $this->getNodeValue($node, 'IM'),
            'xNome'    => $this->getNodeValue($node, 'xNome'),
            'fone'     => $this->getNodeValue($node, 'fone'),
            'email'    => $this->getNodeValue($node, 'email'),
            'endereco' => $endNode !== null ? $this->parseEndereco($endNode) : null,
        ];
    }

    /**
     * TCEndereco: o xs:choice (endNac|endExt) carrega apenas cMun+CEP ou cPais+cEndPost+xCidade+xEstProvReg.
     * xLgr, nro, xCpl, xBairro são filhos diretos de <end>, fora do choice.
     * @return array<string, mixed>
     */
    private function parseEndereco(\DOMElement $end): array
    {
        $endNacNode = $end->getElementsByTagName('endNac')->item(0);
        $endExtNode = $end->getElementsByTagName('endExt')->item(0);

        $base = [
            'xLgr'   => $this->getNodeValue($end, 'xLgr'),
            'nro'    => $this->getNodeValue($end, 'nro'),
            'xCpl'   => $this->getNodeValue($end, 'xCpl'),
            'xBairro' => $this->getNodeValue($end, 'xBairro'),
        ];

        if ($endNacNode !== null) {
            return array_merge($base, [
                'tipo' => 'nacional',
                'cMun' => $this->getNodeValue($endNacNode, 'cMun'),
                'cep'  => $this->getNodeValue($endNacNode, 'CEP'),
            ]);
        }

        if ($endExtNode !== null) {
            return array_merge($base, [
                'tipo'        => 'exterior',
                'cPais'       => $this->getNodeValue($endExtNode, 'cPais'),
                'cEndPost'    => $this->getNodeValue($endExtNode, 'cEndPost'),
                'xCidade'     => $this->getNodeValue($endExtNode, 'xCidade'),
                'xEstProvReg' => $this->getNodeValue($endExtNode, 'xEstProvReg'),
            ]);
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseServico(\DOMElement $serv): array
    {
        $locPrestNode  = $serv->getElementsByTagName('locPrest')->item(0);
        $cServNode     = $serv->getElementsByTagName('cServ')->item(0);
        $comExtNode    = $serv->getElementsByTagName('comExt')->item(0);
        $obraNode      = $serv->getElementsByTagName('obra')->item(0);
        $atvEventoNode = $serv->getElementsByTagName('atvEvento')->item(0);
        $infoComplNode = $serv->getElementsByTagName('infoCompl')->item(0);

        $gItemPedNode  = $infoComplNode?->getElementsByTagName('gItemPed')->item(0);

        $comExt = null;
        if ($comExtNode !== null) {
            $comExt = [
                'mdPrestacao' => $this->getNodeValue($comExtNode, 'mdPrestacao'),
                'vincPrest'   => $this->getNodeValue($comExtNode, 'vincPrest'),
                'tpMoeda'     => $this->getNodeValue($comExtNode, 'tpMoeda'),
                'vServMoeda'  => $this->getNodeValue($comExtNode, 'vServMoeda'),
                'mecAFComexP' => $this->getNodeValue($comExtNode, 'mecAFComexP'),
                'mecAFComexT' => $this->getNodeValue($comExtNode, 'mecAFComexT'),
                'movTempBens' => $this->getNodeValue($comExtNode, 'movTempBens'),
                'nDI'         => $this->getNodeValue($comExtNode, 'nDI'),
                'nRE'         => $this->getNodeValue($comExtNode, 'nRE'),
                'mdic'        => $this->getNodeValue($comExtNode, 'mdic'),
            ];
        }

        return [
            'locPrestacao' => $locPrestNode !== null ? [
                'cLocPrestacao'  => $this->getNodeValue($locPrestNode, 'cLocPrestacao'),
                'cPaisPrestacao' => $this->getNodeValue($locPrestNode, 'cPaisPrestacao'),
            ] : null,
            'cServ' => $cServNode !== null ? [
                'cTribNac'    => $this->getNodeValue($cServNode, 'cTribNac'),
                'cTribMun'    => $this->getNodeValue($cServNode, 'cTribMun'),
                'xDescServ'   => $this->getNodeValue($cServNode, 'xDescServ'),
                'cNBS'        => $this->getNodeValue($cServNode, 'cNBS'),
                'cIntContrib' => $this->getNodeValue($cServNode, 'cIntContrib'),
            ] : null,
            'comExt' => $comExt,
            'obra' => $obraNode !== null ? $this->parseObra($obraNode) : null,
            'atvEvento' => $atvEventoNode !== null ? $this->parseAtvEvento($atvEventoNode) : null,
            'infoCompl' => $infoComplNode !== null ? [
                'idDocTec'  => $this->getNodeValue($infoComplNode, 'idDocTec'),
                'docRef'    => $this->getNodeValue($infoComplNode, 'docRef'),
                'xPed'      => $this->getNodeValue($infoComplNode, 'xPed'),
                'gItemPed'  => $gItemPedNode !== null ? $this->parseGItemPed($gItemPedNode) : null,
                'xInfComp'  => $this->getNodeValue($infoComplNode, 'xInfComp'),
            ] : null,
        ];
    }

    /**
     * TCInfoItemPed: xItemPed com maxOccurs=99 — retorna array de strings.
     * @return list<string>
     */
    private function parseGItemPed(\DOMElement $gItemPed): array
    {
        $items = [];
        foreach ($gItemPed->getElementsByTagName('xItemPed') as $node) {
            $value = trim($node->textContent);
            if ($value !== '') {
                $items[] = $value;
            }
        }
        return $items;
    }

    /**
     * TCInfoObra: inscImobFisc + xs:choice (cObra|cCIB|end).
     * @return array<string, mixed>
     */
    private function parseObra(\DOMElement $obra): array
    {
        $endNode = $obra->getElementsByTagName('end')->item(0);

        $endObra = null;
        if ($endNode !== null) {
            $endExtNode = $endNode->getElementsByTagName('endExt')->item(0);
            if ($endExtNode !== null) {
                // TCEnderExtSimples: cEndPost, xCidade, xEstProvReg (sem cPais)
                $endObra = [
                    'tipo'        => 'exterior',
                    'cEndPost'    => $this->getNodeValue($endExtNode, 'cEndPost'),
                    'xCidade'     => $this->getNodeValue($endExtNode, 'xCidade'),
                    'xEstProvReg' => $this->getNodeValue($endExtNode, 'xEstProvReg'),
                ];
            } else {
                $endObra = [
                    'tipo'    => 'nacional',
                    'cep'     => $this->getNodeValue($endNode, 'CEP'),
                    'xLgr'    => $this->getNodeValue($endNode, 'xLgr'),
                    'nro'     => $this->getNodeValue($endNode, 'nro'),
                    'xCpl'    => $this->getNodeValue($endNode, 'xCpl'),
                    'xBairro' => $this->getNodeValue($endNode, 'xBairro'),
                ];
            }
        }

        return [
            'inscImobFisc' => $this->getNodeValue($obra, 'inscImobFisc'),
            'cObra'        => $this->getNodeValue($obra, 'cObra'),
            'cCIB'         => $this->getNodeValue($obra, 'cCIB'),
            'end'          => $endObra,
        ];
    }

    /**
     * TCAtvEvento: xNome + dtIni + dtFim + xs:choice (idAtvEvt|end).
     * @return array<string, mixed>
     */
    private function parseAtvEvento(\DOMElement $atvEvento): array
    {
        $endNode = $atvEvento->getElementsByTagName('end')->item(0);

        $endEvento = null;
        if ($endNode !== null) {
            $endExtNode = $endNode->getElementsByTagName('endExt')->item(0);
            if ($endExtNode !== null) {
                // TCEnderExtSimples: cEndPost, xCidade, xEstProvReg (sem cPais)
                $endEvento = [
                    'tipo'        => 'exterior',
                    'cEndPost'    => $this->getNodeValue($endExtNode, 'cEndPost'),
                    'xCidade'     => $this->getNodeValue($endExtNode, 'xCidade'),
                    'xEstProvReg' => $this->getNodeValue($endExtNode, 'xEstProvReg'),
                ];
            } else {
                $endEvento = [
                    'tipo'    => 'nacional',
                    'cep'     => $this->getNodeValue($endNode, 'CEP'),
                    'xLgr'    => $this->getNodeValue($endNode, 'xLgr'),
                    'nro'     => $this->getNodeValue($endNode, 'nro'),
                    'xCpl'    => $this->getNodeValue($endNode, 'xCpl'),
                    'xBairro' => $this->getNodeValue($endNode, 'xBairro'),
                ];
            }
        }

        return [
            'xNome'    => $this->getNodeValue($atvEvento, 'xNome'),
            'dtIni'    => $this->getNodeValue($atvEvento, 'dtIni'),
            'dtFim'    => $this->getNodeValue($atvEvento, 'dtFim'),
            'idAtvEvt' => $this->getNodeValue($atvEvento, 'idAtvEvt'),
            'end'      => $endEvento,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseValoresDps(\DOMElement $valores): array
    {
        $vServPrestNode      = $valores->getElementsByTagName('vServPrest')->item(0);
        $vDescCondIncondNode = $valores->getElementsByTagName('vDescCondIncond')->item(0);
        $vDedRedNode         = $valores->getElementsByTagName('vDedRed')->item(0);
        $tribNode            = $valores->getElementsByTagName('trib')->item(0);
        $tribMunNode         = $tribNode?->getElementsByTagName('tribMun')->item(0);
        $tribFedNode         = $tribNode?->getElementsByTagName('tribFed')->item(0);
        $totTribNode         = $tribNode?->getElementsByTagName('totTrib')->item(0);
        $exigSuspNode        = $tribMunNode?->getElementsByTagName('exigSusp')->item(0);
        $bmNode              = $tribMunNode?->getElementsByTagName('BM')->item(0);
        $pisCofinsNode       = $tribFedNode?->getElementsByTagName('piscofins')->item(0);

        // TCTotTrib uses xs:choice: vTotTrib (monetary) | pTotTrib (percent) | indTotTrib | pTotTribSN
        $vTotTribNode = $totTribNode?->getElementsByTagName('vTotTrib')->item(0);
        $pTotTribNode = $totTribNode?->getElementsByTagName('pTotTrib')->item(0);

        $totTrib = null;
        if ($totTribNode !== null) {
            if ($vTotTribNode !== null) {
                $totTrib = [
                    'tipo'       => 'monetario',
                    'vTotTribFed' => $this->getNodeValue($vTotTribNode, 'vTotTribFed'),
                    'vTotTribEst' => $this->getNodeValue($vTotTribNode, 'vTotTribEst'),
                    'vTotTribMun' => $this->getNodeValue($vTotTribNode, 'vTotTribMun'),
                ];
            } elseif ($pTotTribNode !== null) {
                $totTrib = [
                    'tipo'       => 'percentual',
                    'pTotTribFed' => $this->getNodeValue($pTotTribNode, 'pTotTribFed'),
                    'pTotTribEst' => $this->getNodeValue($pTotTribNode, 'pTotTribEst'),
                    'pTotTribMun' => $this->getNodeValue($pTotTribNode, 'pTotTribMun'),
                ];
            } else {
                $totTrib = [
                    'tipo'        => 'indicador',
                    'indTotTrib'  => $this->getNodeValue($totTribNode, 'indTotTrib'),
                    'pTotTribSN'  => $this->getNodeValue($totTribNode, 'pTotTribSN'),
                ];
            }
        }

        // TCInfoDedRed uses xs:choice: pDR | vDR | documentos
        $vDedRed = null;
        if ($vDedRedNode !== null) {
            $vDedRed = [
                'pDR' => $this->getNodeValue($vDedRedNode, 'pDR'),
                'vDR' => $this->getNodeValue($vDedRedNode, 'vDR'),
            ];
        }

        return [
            'vServPrest' => $vServPrestNode !== null ? [
                'vReceb' => $this->getNodeValue($vServPrestNode, 'vReceb'),
                'vServ'  => $this->getNodeValue($vServPrestNode, 'vServ'),
            ] : null,
            'vDescCondIncond' => $vDescCondIncondNode !== null ? [
                'vDescIncond' => $this->getNodeValue($vDescCondIncondNode, 'vDescIncond'),
                'vDescCond'   => $this->getNodeValue($vDescCondIncondNode, 'vDescCond'),
            ] : null,
            'vDedRed' => $vDedRed,
            'tribMun' => $tribMunNode !== null ? [
                'tribISSQN'   => $this->getNodeValue($tribMunNode, 'tribISSQN'),
                'cPaisResult' => $this->getNodeValue($tribMunNode, 'cPaisResult'),
                'tpImunidade' => $this->getNodeValue($tribMunNode, 'tpImunidade'),
                'exigSusp'    => $exigSuspNode !== null ? [
                    'tpSusp'    => $this->getNodeValue($exigSuspNode, 'tpSusp'),
                    'nProcesso' => $this->getNodeValue($exigSuspNode, 'nProcesso'),
                ] : null,
                'BM' => $bmNode !== null ? [
                    'nBM'      => $this->getNodeValue($bmNode, 'nBM'),
                    'vRedBCBM' => $this->getNodeValue($bmNode, 'vRedBCBM'),
                    'pRedBCBM' => $this->getNodeValue($bmNode, 'pRedBCBM'),
                ] : null,
                'tpRetISSQN'  => $this->getNodeValue($tribMunNode, 'tpRetISSQN'),
                'pAliq'       => $this->getNodeValue($tribMunNode, 'pAliq'),
            ] : null,
            'tribFed' => $tribFedNode !== null ? [
                'piscofins' => $pisCofinsNode !== null ? [
                    'CST'           => $this->getNodeValue($pisCofinsNode, 'CST'),
                    'vBCPisCofins'  => $this->getNodeValue($pisCofinsNode, 'vBCPisCofins'),
                    'pAliqPis'      => $this->getNodeValue($pisCofinsNode, 'pAliqPis'),
                    'pAliqCofins'   => $this->getNodeValue($pisCofinsNode, 'pAliqCofins'),
                    'vPis'          => $this->getNodeValue($pisCofinsNode, 'vPis'),
                    'vCofins'       => $this->getNodeValue($pisCofinsNode, 'vCofins'),
                    'tpRetPisCofins' => $this->getNodeValue($pisCofinsNode, 'tpRetPisCofins'),
                ] : null,
                'vRetCP'   => $this->getNodeValue($tribFedNode, 'vRetCP'),
                'vRetIRRF' => $this->getNodeValue($tribFedNode, 'vRetIRRF'),
                'vRetCSLL' => $this->getNodeValue($tribFedNode, 'vRetCSLL'),
            ] : null,
            'totTrib' => $totTrib,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseIbscbs(\DOMElement $infNfse): ?array
    {
        // Busca apenas o IBSCBS filho direto de infNFSe (TCRTCIBSCBS — gerado pela Sefin).
        // getElementsByTagName é recursivo e apanharia também o IBSCBS dentro de DPS/infDPS.
        $ibscbs = null;
        foreach ($infNfse->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === 'IBSCBS') {
                $ibscbs = $child;
                break;
            }
        }

        if ($ibscbs === null) {
            return null;
        }

        $valores = $ibscbs->getElementsByTagName('valores')->item(0);
        $totNode = $ibscbs->getElementsByTagName('totCIBS')->item(0);

        $uf = null;
        $ufNode = $valores?->getElementsByTagName('uf')->item(0);
        if ($ufNode !== null) {
            $uf = [
                'pIBSUF'     => $this->getNodeValue($ufNode, 'pIBSUF'),
                'pRedAliqUF' => $this->getNodeValue($ufNode, 'pRedAliqUF'),
                'pAliqEfetUF' => $this->getNodeValue($ufNode, 'pAliqEfetUF'),
            ];
        }

        $mun = null;
        $munNode = $valores?->getElementsByTagName('mun')->item(0);
        if ($munNode !== null) {
            $mun = [
                'pIBSMun'    => $this->getNodeValue($munNode, 'pIBSMun'),
                'pRedAliqMun' => $this->getNodeValue($munNode, 'pRedAliqMun'),
                'pAliqEfetMun' => $this->getNodeValue($munNode, 'pAliqEfetMun'),
            ];
        }

        $fed = null;
        $fedNode = $valores?->getElementsByTagName('fed')->item(0);
        if ($fedNode !== null) {
            $fed = [
                'pCBS'       => $this->getNodeValue($fedNode, 'pCBS'),
                'pRedAliqCBS' => $this->getNodeValue($fedNode, 'pRedAliqCBS'),
                'pAliqEfetCBS' => $this->getNodeValue($fedNode, 'pAliqEfetCBS'),
            ];
        }

        $totCibs = null;
        if ($totNode !== null) {
            $gIbsNode = $totNode->getElementsByTagName('gIBS')->item(0);
            $gCbsNode = $totNode->getElementsByTagName('gCBS')->item(0);

            $gIbs = null;
            if ($gIbsNode !== null) {
                $gIbsCredPresNode = $gIbsNode->getElementsByTagName('gIBSCredPres')->item(0);
                $gIbsUfTotNode    = $gIbsNode->getElementsByTagName('gIBSUFTot')->item(0);
                $gIbsMunTotNode   = $gIbsNode->getElementsByTagName('gIBSMunTot')->item(0);

                $gIbs = [
                    'vIBSTot' => $this->getNodeValue($gIbsNode, 'vIBSTot'),
                    'gIBSCredPres' => $gIbsCredPresNode !== null ? [
                        'pCredPresIBS' => $this->getNodeValue($gIbsCredPresNode, 'pCredPresIBS'),
                        'vCredPresIBS' => $this->getNodeValue($gIbsCredPresNode, 'vCredPresIBS'),
                    ] : null,
                    'gIBSUFTot' => $gIbsUfTotNode !== null ? [
                        'vDifUF' => $this->getNodeValue($gIbsUfTotNode, 'vDifUF'),
                        'vIBSUF' => $this->getNodeValue($gIbsUfTotNode, 'vIBSUF'),
                    ] : null,
                    'gIBSMunTot' => $gIbsMunTotNode !== null ? [
                        'vDifMun' => $this->getNodeValue($gIbsMunTotNode, 'vDifMun'),
                        'vIBSMun' => $this->getNodeValue($gIbsMunTotNode, 'vIBSMun'),
                    ] : null,
                ];
            }

            $gCbs = null;
            if ($gCbsNode !== null) {
                $gCbsCredPresNode = $gCbsNode->getElementsByTagName('gCBSCredPres')->item(0);

                $gCbs = [
                    'vCBS' => $this->getNodeValue($gCbsNode, 'vCBS'),
                    'vDifCBS' => $this->getNodeValue($gCbsNode, 'vDifCBS'),
                    'gCBSCredPres' => $gCbsCredPresNode !== null ? [
                        'pCredPresCBS' => $this->getNodeValue($gCbsCredPresNode, 'pCredPresCBS'),
                        'vCredPresCBS' => $this->getNodeValue($gCbsCredPresNode, 'vCredPresCBS'),
                    ] : null,
                ];
            }

            $gTribRegularNode   = $totNode->getElementsByTagName('gTribRegular')->item(0);
            $gTribCompraGovNode = $totNode->getElementsByTagName('gTribCompraGov')->item(0);

            $totCibs = [
                'vTotNF' => $this->getNodeValue($totNode, 'vTotNF'),
                'gIBS'   => $gIbs,
                'gCBS'   => $gCbs,
                'gTribRegular' => $gTribRegularNode !== null ? [
                    'pAliqEfeRegIBSUF'  => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegIBSUF'),
                    'vTribRegIBSUF'     => $this->getNodeValue($gTribRegularNode, 'vTribRegIBSUF'),
                    'pAliqEfeRegIBSMun' => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegIBSMun'),
                    'vTribRegIBSMun'    => $this->getNodeValue($gTribRegularNode, 'vTribRegIBSMun'),
                    'pAliqEfeRegCBS'    => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegCBS'),
                    'vTribRegCBS'       => $this->getNodeValue($gTribRegularNode, 'vTribRegCBS'),
                ] : null,
                'gTribCompraGov' => $gTribCompraGovNode !== null ? [
                    'pIBSUF' => $this->getNodeValue($gTribCompraGovNode, 'pIBSUF'),
                    'vIBSUF' => $this->getNodeValue($gTribCompraGovNode, 'vIBSUF'),
                    'pIBSMun' => $this->getNodeValue($gTribCompraGovNode, 'pIBSMun'),
                    'vIBSMun' => $this->getNodeValue($gTribCompraGovNode, 'vIBSMun'),
                    'pCBS'   => $this->getNodeValue($gTribCompraGovNode, 'pCBS'),
                    'vCBS'   => $this->getNodeValue($gTribCompraGovNode, 'vCBS'),
                ] : null,
            ];
        }

        return [
            'cLocalidadeIncid' => $this->getNodeValue($ibscbs, 'cLocalidadeIncid'),
            'xLocalidadeIncid' => $this->getNodeValue($ibscbs, 'xLocalidadeIncid'),
            'pRedutor'         => $this->getNodeValue($ibscbs, 'pRedutor'),
            'valores' => [
                'vBC'           => $valores !== null ? $this->getNodeValue($valores, 'vBC') : null,
                'vCalcReeRepRes' => $valores !== null ? $this->getNodeValue($valores, 'vCalcReeRepRes') : null,
                'uf'  => $uf,
                'mun' => $mun,
                'fed' => $fed,
            ],
            'totCIBS' => $totCibs,
        ];
    }

    private function getNodeValue(\DOMElement $parent, string $tagName, int $occurrence = 0): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length > $occurrence) {
            $node = $nodes->item($occurrence);
            if ($node === null) {
                return null;
            }
            return trim($node->textContent);
        }

        return null;
    }
}
