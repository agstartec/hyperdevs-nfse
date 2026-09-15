<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\Service;

use Hyperdevs\Nfse\Application\DTO\Request\DpsRequest;
use Hyperdevs\Nfse\Application\DTO\Response\NfseResponse;
use Hyperdevs\Nfse\Application\Exception\ServiceException;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Application\Validator\DpsValidator;
use Hyperdevs\Nfse\Application\Validator\IbscbsResponseValidator;
use Hyperdevs\Nfse\Domain\Entity\Dps;
use Hyperdevs\Nfse\Domain\Entity\Endereco;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsDest;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsDiferimento;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsDocumentoReeRepRes;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoExterior;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoObra;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsFornecedor;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsImovel;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsInfo;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsReeRepRes;
use Hyperdevs\Nfse\Domain\Entity\IbsCbsTribRegular;
use Hyperdevs\Nfse\Domain\Entity\Intermediario;
use Hyperdevs\Nfse\Domain\Entity\Obra;
use Hyperdevs\Nfse\Domain\Entity\Prestador;
use Hyperdevs\Nfse\Domain\Entity\Servico;
use Hyperdevs\Nfse\Domain\Entity\Substituicao;
use Hyperdevs\Nfse\Domain\Entity\Tomador;
use Hyperdevs\Nfse\Domain\Enum\FinalidadeNfse;
use Hyperdevs\Nfse\Domain\Enum\IndicadorDestinacao;
use Hyperdevs\Nfse\Domain\Enum\IndicadorFinal;
use Hyperdevs\Nfse\Domain\Enum\MotivoEmissaoTI;
use Hyperdevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperdevs\Nfse\Domain\Enum\TipoEmitente;
use Hyperdevs\Nfse\Domain\Enum\TipoEnteGovernamental;
use Hyperdevs\Nfse\Domain\Enum\TipoOperacao;
use Hyperdevs\Nfse\Domain\Enum\TipoRetencaoIssqn;
use Hyperdevs\Nfse\Domain\Enum\TributacaoIssqn;
use Hyperdevs\Nfse\Domain\Exception\DomainException;
use Hyperdevs\Nfse\Domain\ValueObject\Cep;
use Hyperdevs\Nfse\Domain\ValueObject\ChaveAcesso;
use Hyperdevs\Nfse\Domain\ValueObject\Cnpj;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoCIB;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoClassificacaoTributaria;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoCreditoPresumido;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoIndicadorOperacao;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoMunicipio;
use Hyperdevs\Nfse\Domain\ValueObject\CodigoSituacaoTributaria;
use Hyperdevs\Nfse\Domain\ValueObject\Cpf;
use Hyperdevs\Nfse\Domain\ValueObject\Money;
use Hyperdevs\Nfse\Config\ApiEndpoints;
use Hyperdevs\Nfse\Http\Contract\ApiConnectorInterface;
use Hyperdevs\Nfse\Http\Exception\HttpException;
use Hyperdevs\Nfse\Http\RequestBuilder;
use Hyperdevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperdevs\Nfse\Xml\Builder\Contract\XmlBuilderInterface;
use Hyperdevs\Nfse\Xml\Parser\NfseXmlParser;
use Hyperdevs\Nfse\Xml\Validator\Contract\XsdValidatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EmitirDpsService
{
    use SefinErrorMessageTrait;

    public function __construct(
        private ApiConnectorInterface $apiConnector,
        private XmlBuilderInterface $xmlBuilder,
        private XmlSignerInterface $xmlSigner,
        private XsdValidatorInterface $xsdValidator,
        private DpsValidator $validator,
        private RequestBuilder $requestBuilder,
        private NfseXmlParser $nfseXmlParser,
        private IbscbsResponseValidator $ibscbsResponseValidator,
        private ApiEndpoints $apiEndpoints,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function executar(DpsRequest $request): NfseResponse
    {
        try {
            $this->validator->validate($request);

            $dps = $this->criarDpsFromRequest($request);
            $dps->gerarChaveAcesso();

            $xml = $this->xmlBuilder->build($dps);

            $this->xsdValidator->validate($xml, 'DPS');

            $xmlAssinado = $this->xmlSigner->sign($xml, 'infDPS', 'DPS');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $payload = $this->requestBuilder->buildDpsPayload($xmlAssinado);

            $response = $this->apiConnector->post('nfse', $payload);

            return $this->processarResposta($response, $dps);

        } catch (DomainException $e) {
            $this->logger->warning('Validação DPS falhou: {msg}', ['msg' => $e->getMessage()]);
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao emitir DPS: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao comunicar com API: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function executarPorDecisaoJudicial(string $nfseXml): NfseResponse
    {
        try {
            $this->xsdValidator->validate($nfseXml, 'NFSe');

            $xmlAssinado = $this->xmlSigner->sign($nfseXml, 'infNFSe', 'NFSe');
            $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

            $gzipBase64 = $this->compactarXml($xmlAssinado);

            $payload = ['xmlGZipB64' => $gzipBase64];

            $endpoint = $this->apiEndpoints->decisaoJudicialNfse();
            $response = $this->apiConnector->post($endpoint, $payload);

            return $this->processarRespostaDecisaoJudicial($response);

        } catch (DomainException $e) {
            $this->logger->warning('Validação decisão judicial falhou: {msg}', ['msg' => $e->getMessage()]);
            throw new ValidationException(
                "Dados inválidos: {$e->getMessage()}",
                0,
                $e
            );
        } catch (HttpException $e) {
            $this->logger->error('Falha HTTP ao emitir por decisão judicial: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao comunicar com API: {$e->getMessage()}",
                0,
                $e
            );
        } catch (\RuntimeException $e) {
            $this->logger->error('Falha ao processar XML para decisão judicial: {msg}', ['msg' => $e->getMessage()]);
            throw new ServiceException(
                "Falha ao processar XML: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function compactarXml(string $xml): string
    {
        $compressed = gzencode($xml);
        if ($compressed === false) {
            throw new ServiceException('Falha ao compactar XML');
        }

        return base64_encode($compressed);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processarRespostaDecisaoJudicial(array $response): NfseResponse
    {
        if (!$response['success']) {
            return new NfseResponse(
                success: false,
                mensagem: $this->extrairMensagemErro($response['data'] ?? null, 'Falha na emissão por decisão judicial'),
                dados: is_array($response['data'] ?? null) ? $response['data'] : null,
                erros: $this->extrairErros($response['data'] ?? null),
            );
        }

        $data = $response['data'] ?? [];
        $nfseXmlGzip = $data['nfseXmlGZipB64'] ?? null;

        if ($nfseXmlGzip) {
            $xml = $this->descompactarXml($nfseXmlGzip);
            $parsed = $this->nfseXmlParser->parse($xml);

            return new NfseResponse(
                success: true,
                dados: $parsed[0] ?? null,
                xml: $xml,
            );
        }

        return new NfseResponse(
            success: true,
            dados: $data,
        );
    }

    private function descompactarXml(string $gzipBase64): string
    {
        $decoded = base64_decode($gzipBase64, true);
        if ($decoded === false) {
            throw new ServiceException('Falha ao decodificar base64');
        }

        $uncompressed = gzdecode($decoded);
        if ($uncompressed === false) {
            throw new ServiceException('Falha ao descompactar gzip');
        }

        return $uncompressed;
    }

    private function criarDpsFromRequest(DpsRequest $request): Dps
    {
        $documentoPrestador = null;
        if ($request->prestador->documento !== null && $request->prestador->isCnpj !== null) {
            $documentoPrestador = $request->prestador->isCnpj
                ? new Cnpj($request->prestador->documento)
                : new Cpf($request->prestador->documento);
        }
        $enderecoPrestador = $this->criarEnderecoPessoa(
            $request->prestador->logradouro,
            $request->prestador->numero,
            $request->prestador->complemento,
            $request->prestador->bairro,
            $request->prestador->codigoMunicipio,
            $request->prestador->cep,
        );

        $prestador = new Prestador(
            documento: $documentoPrestador,
            inscricaoMunicipal: $request->prestador->inscricaoMunicipal,
            razaoSocial: $request->prestador->razaoSocial,
            telefone: $request->prestador->telefone ? new \Hyperdevs\Nfse\Domain\ValueObject\Telefone($request->prestador->telefone) : null,
            email: $request->prestador->email ? new \Hyperdevs\Nfse\Domain\ValueObject\Email($request->prestador->email) : null,
            endereco: $enderecoPrestador,
            regimeTributario: \Hyperdevs\Nfse\Domain\Enum\RegimeTributario::from($request->prestador->regimeTributario),
            regimeEspecialTributacao: $request->prestador->regEspTrib !== null
                ? \Hyperdevs\Nfse\Domain\Enum\RegimeEspecialTributacao::from((string) $request->prestador->regEspTrib)
                : \Hyperdevs\Nfse\Domain\Enum\RegimeEspecialTributacao::NENHUM,
            nif: $request->prestador->nif,
            caepf: $request->prestador->caepf,
            codigoNaoNif: $request->prestador->codigoNaoNif,
            regimeApuracaoSimplesNacional: $request->prestador->regApTribSN,
        );

        $tomador = null;
        if ($request->tomador !== null) {
            $documentoTomador = null;
            if ($request->tomador->documento) {
                $documentoTomador = $request->tomador->isCnpj
                    ? new Cnpj($request->tomador->documento)
                    : new Cpf($request->tomador->documento);
            }

            $tomador = new Tomador(
                documento: $documentoTomador,
                razaoSocial: $request->tomador->razaoSocial,
                telefone: $request->tomador->telefone ? new \Hyperdevs\Nfse\Domain\ValueObject\Telefone($request->tomador->telefone) : null,
                email: $request->tomador->email ? new \Hyperdevs\Nfse\Domain\ValueObject\Email($request->tomador->email) : null,
                endereco: $this->criarEnderecoPessoa(
                    $request->tomador->logradouro,
                    $request->tomador->numero,
                    $request->tomador->complemento,
                    $request->tomador->bairro,
                    $request->tomador->codigoMunicipio,
                    $request->tomador->cep,
                    $request->tomador->codigoPais,
                    $request->tomador->codigoPostalExterior,
                    $request->tomador->nomeCidadeExterior,
                    $request->tomador->estadoProvinciaExterior,
                ),
                nif: $request->tomador->nif,
                inscricaoMunicipal: $request->tomador->inscricaoMunicipal,
                codigoNaoNif: $request->tomador->codigoNaoNif,
                caepf: $request->tomador->caepf,
            );
        }

        $intermediario = null;
        if ($request->intermediario !== null) {
            $i = $request->intermediario;
            $documentoIntermediario = null;
            if ($i->documento) {
                $documentoIntermediario = $i->isCnpj
                    ? new Cnpj($i->documento)
                    : new Cpf($i->documento);
            }

            $intermediario = new Intermediario(
                documento: $documentoIntermediario,
                razaoSocial: $i->razaoSocial,
                inscricaoMunicipal: $i->inscricaoMunicipal,
                telefone: $i->telefone ? new \Hyperdevs\Nfse\Domain\ValueObject\Telefone($i->telefone) : null,
                email: $i->email ? new \Hyperdevs\Nfse\Domain\ValueObject\Email($i->email) : null,
                endereco: $this->criarEnderecoPessoa(
                    $i->logradouro,
                    $i->numero,
                    $i->complemento,
                    $i->bairro,
                    $i->codigoMunicipio,
                    $i->cep,
                    $i->codigoPais,
                    $i->codigoPostalExterior,
                    $i->nomeCidadeExterior,
                    $i->estadoProvinciaExterior,
                ),
                nif: $i->nif,
                codigoNaoNif: $i->codigoNaoNif,
                caepf: $i->caepf,
            );
        }

        $obra = null;
        if ($request->servico->obra !== null) {
            $o = $request->servico->obra;
            $endObra = null;
            if ($o->endereco !== null) {
                $e = $o->endereco;
                $endExt = null;
                if ($e->endExt !== null) {
                    $endExt = new \Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoExterior(
                        cEndPost: $e->endExt->cEndPost,
                        xCidade: $e->endExt->xCidade,
                        xEstProvReg: $e->endExt->xEstProvReg,
                    );
                }
                $endObra = new \Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoObra(
                    cep: $e->cep,
                    endExt: $endExt,
                    xLgr: $e->xLgr,
                    nro: $e->nro,
                    xCpl: $e->xCpl,
                    xBairro: $e->xBairro,
                );
            }
            $obra = new Obra(
                inscImobFisc: $o->inscImobFisc,
                cObra: $o->cObra,
                cCIB: $o->cCIB !== null ? new CodigoCIB($o->cCIB) : null,
                endereco: $endObra,
            );
        }

        $servico = new Servico(
            discriminacao: $request->servico->discriminacao,
            codigoTributacao: $request->servico->codigoTributacao,
            valorServicos: new Money($request->servico->valorServicos),
            descontoIncondicionado: $request->servico->descontoIncondicionado !== null ? new Money($request->servico->descontoIncondicionado) : null,
            descontoCondicionado: $request->servico->descontoCondicionado !== null ? new Money($request->servico->descontoCondicionado) : null,
            aliquotaIss: $request->servico->aliquotaIss,
            localPrestacao: $request->servico->codigoMunicipioPrestacao !== null ? new CodigoMunicipio($request->servico->codigoMunicipioPrestacao) : null,
            codigoNbs: $request->servico->codigoNbs,
            obra: $obra,
            tribISSQN: $request->servico->tribISSQN !== null ? TributacaoIssqn::from($request->servico->tribISSQN) : TributacaoIssqn::OPERACAO_TRIBUTAVEL,
            tpRetISSQN: $request->servico->tpRetISSQN !== null ? TipoRetencaoIssqn::from($request->servico->tpRetISSQN) : TipoRetencaoIssqn::NAO_RETIDO,
            codigoPaisPrestacao: $request->servico->codigoPaisPrestacao,
            codigoPaisResultado: $request->servico->codigoPaisResultado,
            codigoTributacaoMunicipal: $request->servico->codigoTributacaoMunicipal,
            codigoInternoContribuinte: $request->servico->codigoInternoContribuinte,
            valorRecebido: $request->servico->valorRecebido,
            comExterior: $this->criarComExterior($request->servico->comExterior),
            atvEvento: $this->criarAtvEvento($request->servico->atvEvento),
            infoCompl: $this->criarInfoCompl($request->servico->infoCompl),
            documentosDeducao: $this->criarDocumentosDeducao($request->servico->documentosDeducao),
            percentualDeducao: $request->servico->percentualDeducao,
            valorDeducaoPadrao: $request->servico->valorDeducaoPadrao,
            tipoImunidade: $request->servico->tipoImunidade,
            exigSusp: $this->criarExigSusp($request->servico->exigSusp),
            beneficioMunicipal: $this->criarBeneficioMunicipal($request->servico->beneficioMunicipal),
            tribFederal: $this->criarTribFederal($request->servico->tribFederal),
            totTribTipo: $request->servico->totTribTipo,
            pTotTribFed: $request->servico->pTotTribFed,
            pTotTribEst: $request->servico->pTotTribEst,
            pTotTribMun: $request->servico->pTotTribMun,
            indTotTrib: $request->servico->indTotTrib,
            pTotTribSN: $request->servico->pTotTribSN,
            vTotTribFed: $request->servico->vTotTribFed,
            vTotTribEst: $request->servico->vTotTribEst,
            vTotTribMun: $request->servico->vTotTribMun,
        );

        $substituicao = null;
        if ($request->substituicao !== null) {
            $substituicao = new Substituicao(
                chaveSubstituida: new ChaveAcesso($request->substituicao->chaveSubstituida),
                codigoMotivo: $request->substituicao->codigoMotivo,
                descricaoMotivo: $request->substituicao->descricaoMotivo,
            );
        }

        $ibscbs = null;
        if ($request->ibscbs !== null) {
            $req = $request->ibscbs;

            $dest = null;
            if ($req->dest !== null) {
                $d = $req->dest;
                $docDest = null;
                if ($d->cnpj) {
                    $docDest = new Cnpj($d->cnpj);
                } elseif ($d->cpf) {
                    $docDest = new Cpf($d->cpf);
                }

                $endDest = $this->criarEnderecoPessoa(
                    $d->logradouro,
                    $d->numero,
                    $d->complemento,
                    $d->bairro,
                    $d->codigoMunicipio,
                    $d->cep,
                    $d->codigoPais,
                    $d->codigoPostalExterior,
                    $d->nomeCidadeExterior,
                    $d->estadoProvinciaExterior,
                );

                $dest = new IbsCbsDest(
                    cnpj: $docDest instanceof Cnpj ? $docDest : null,
                    cpf: $docDest instanceof Cpf ? $docDest : null,
                    nif: $d->nif ? new \Hyperdevs\Nfse\Domain\ValueObject\Nif($d->nif) : null,
                    codigoNaoNif: $d->codigoNaoNif,
                    xNome: $d->xNome,
                    endereco: $endDest,
                    fone: $d->fone,
                    email: $d->email,
                );
            }

            $tribRegular = null;
            if ($req->tribRegular !== null) {
                $tribRegular = new IbsCbsTribRegular(
                    cstReg: new CodigoSituacaoTributaria($req->tribRegular->cstReg),
                    cClassTribReg: new CodigoClassificacaoTributaria($req->tribRegular->cClassTribReg),
                );
            }

            $diferimento = null;
            if ($req->diferimento !== null) {
                $diferimento = new IbsCbsDiferimento(
                    pDifUF: $req->diferimento->pDifUF,
                    pDifMun: $req->diferimento->pDifMun,
                    pDifCBS: $req->diferimento->pDifCBS,
                );
            }

            $refNFSeList = null;
            if ($req->refNFSeList !== null) {
                $refNFSeList = array_map(
                    fn (string $chave) => new ChaveAcesso($chave),
                    $req->refNFSeList,
                );
            }

            $imovel = null;
            if ($req->imovel !== null) {
                $im = $req->imovel;
                $endObra = null;
                if ($im->endereco !== null) {
                    $e = $im->endereco;
                    $endExt = null;
                    if ($e->endExt !== null) {
                        $endExt = new IbsCbsEnderecoExterior(
                            cEndPost: $e->endExt->cEndPost,
                            xCidade: $e->endExt->xCidade,
                            xEstProvReg: $e->endExt->xEstProvReg,
                        );
                    }
                    $endObra = new IbsCbsEnderecoObra(
                        cep: $e->cep,
                        endExt: $endExt,
                        xLgr: $e->xLgr,
                        nro: $e->nro,
                        xCpl: $e->xCpl,
                        xBairro: $e->xBairro,
                    );
                }
                $imovel = new IbsCbsImovel(
                    inscImobFisc: $im->inscImobFisc,
                    cCIB: $im->cCIB !== null ? new CodigoCIB($im->cCIB) : null,
                    endereco: $endObra,
                );
            }

            $reeRepRes = null;
            if ($req->reeRepRes !== null) {
                $docs = array_map(
                    fn ($dReq) => $this->criarDocumentoReeRepRes($dReq),
                    $req->reeRepRes->documentos,
                );
                $reeRepRes = new IbsCbsReeRepRes($docs);
            }

            $ibscbs = new IbsCbsInfo(
                finNFSe: FinalidadeNfse::from($req->finNFSe),
                cIndOp: new CodigoIndicadorOperacao($req->cIndOp),
                indDest: IndicadorDestinacao::from($req->indDest),
                cst: new CodigoSituacaoTributaria($req->cst),
                cClassTrib: new CodigoClassificacaoTributaria($req->cClassTrib),
                indFinal: $req->indFinal !== null ? IndicadorFinal::from($req->indFinal) : null,
                tpOper: $req->tpOper !== null ? TipoOperacao::from($req->tpOper) : null,
                tpEnteGov: $req->tpEnteGov !== null ? TipoEnteGovernamental::from($req->tpEnteGov) : null,
                cCredPres: $req->cCredPres !== null ? new CodigoCreditoPresumido($req->cCredPres) : null,
                dest: $dest,
                tribRegular: $tribRegular,
                diferimento: $diferimento,
                refNFSeList: $refNFSeList,
                imovel: $imovel,
                reeRepRes: $reeRepRes,
            );
        }

        return new Dps(
            tipoAmbiente: TipoAmbiente::from($request->tipoAmbiente),
            dataEmissao: new \DateTimeImmutable($request->dataEmissao),
            versaoAplicacao: $request->versaoAplicacao,
            serie: $request->serie,
            numero: $request->numero,
            dataCompetencia: new \DateTimeImmutable($request->dataCompetencia),
            tipoEmissao: TipoEmitente::from($request->tipoEmissao),
            codigoMunicipioEmissor: new CodigoMunicipio($request->codigoMunicipioEmissor),
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
            intermediario: $intermediario,
            substituicao: $substituicao,
            ibscbs: $ibscbs,
            cMotivoEmisTI: $request->cMotivoEmisTI !== null ? MotivoEmissaoTI::from($request->cMotivoEmisTI) : null,
            chNFSeRej: $request->chNFSeRej !== null ? new ChaveAcesso($request->chNFSeRej) : null,
        );
    }

    private function criarDocumentoReeRepRes(\Hyperdevs\Nfse\Application\DTO\Request\IbsCbsDocumentoReeRepResRequest $dReq): IbsCbsDocumentoReeRepRes
    {
        $fornec = null;
        if ($dReq->fornec !== null) {
            $f = $dReq->fornec;
            $fornec = new IbsCbsFornecedor(
                cnpj: $f->cnpj !== null ? new Cnpj($f->cnpj) : null,
                cpf: $f->cpf !== null ? new Cpf($f->cpf) : null,
                nif: $f->nif !== null ? new \Hyperdevs\Nfse\Domain\ValueObject\Nif($f->nif) : null,
                codigoNaoNif: $f->codigoNaoNif,
                xNome: $f->xNome,
            );
        }

        return new IbsCbsDocumentoReeRepRes(
            tipo: $dReq->tipoDocumento,
            dtEmiDoc: new \DateTimeImmutable($dReq->dtEmiDoc),
            dtCompDoc: new \DateTimeImmutable($dReq->dtCompDoc),
            tpReeRepRes: \Hyperdevs\Nfse\Domain\Enum\TipoReembolsoRepasseRessarcimento::from($dReq->tpReeRepRes),
            vlrReeRepRes: (string) $dReq->vlrReeRepRes,
            fornec: $fornec,
            xTpReeRepRes: $dReq->xTpReeRepRes,
            tipoChaveDFe: $dReq->tipoChaveDFe,
            xTipoChaveDFe: $dReq->xTipoChaveDFe,
            chaveDFe: $dReq->chaveDFe,
            cMunDocFiscal: $dReq->cMunDocFiscal,
            nDocFiscal: $dReq->nDocFiscal,
            xDocFiscal: $dReq->xDocFiscal,
            nDoc: $dReq->nDoc,
            xDoc: $dReq->xDoc,
        );
    }

    private function criarComExterior(?\Hyperdevs\Nfse\Application\DTO\Request\ComExteriorRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\ComExterior
    {
        if ($req === null) {
            return null;
        }

        return new \Hyperdevs\Nfse\Domain\Entity\ComExterior(
            modoPrestacao: $this->exigir($req->modoPrestacao, 'mdPrestacao'),
            vinculoPrestador: $this->exigir($req->vinculoPrestador, 'vincPrest'),
            codigoMoeda: $this->exigir($req->codigoMoeda, 'tpMoeda'),
            valorServicoMoeda: $this->exigir($req->valorServicoMoeda, 'vServMoeda'),
            mecanismoApoioPrestador: $this->exigir($req->mecanismoApoioPrestador, 'mecAFComexP'),
            mecanismoApoioTomador: $this->exigir($req->mecanismoApoioTomador, 'mecAFComexT'),
            movimentacaoTemporaria: $this->exigir($req->movimentacaoTemporaria, 'movTempBens'),
            enviarMDIC: $this->exigir($req->enviarMDIC, 'mdic'),
            numeroDeclaracaoImportacao: $req->numeroDeclaracaoImportacao,
            numeroRegistroExportacao: $req->numeroRegistroExportacao,
        );
    }

    /**
     * Garante que um campo obrigatório do XSD foi informado pelo cliente.
     * A biblioteca nunca presume valores fiscais — ausência é erro, não default.
     *
     * @template T
     * @param T|null $valor
     * @return T
     */
    private function exigir(mixed $valor, string $campo): mixed
    {
        if ($valor === null) {
            throw new \InvalidArgumentException(
                "{$campo} é obrigatório e não pode ser presumido pela biblioteca"
            );
        }

        return $valor;
    }

    private function criarAtvEvento(?\Hyperdevs\Nfse\Application\DTO\Request\AtvEventoRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\AtvEvento
    {
        if ($req === null) {
            return null;
        }

        // Campos obrigatórios do XSD (TCAtvEvento e TCEnderObraEvento): sem presunção.
        $endereco = null;
        if ($req->endereco !== null) {
            $endExt = null;
            if ($req->endereco->codigoPais !== null) {
                $endExt = new \Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoExterior(
                    cEndPost: $this->exigir($req->endereco->codigoPostalExterior, 'cEndPost'),
                    xCidade: $this->exigir($req->endereco->nomeCidadeExterior, 'xCidade'),
                    xEstProvReg: $this->exigir($req->endereco->estadoProvinciaExterior, 'xEstProvReg'),
                );
            }
            $endereco = new \Hyperdevs\Nfse\Domain\Entity\IbsCbsEnderecoObra(
                cep: $req->endereco->codigoPais === null ? ($req->endereco->cep ?? null) : null,
                endExt: $endExt,
                xLgr: $this->exigir($req->endereco->logradouro, 'xLgr'),
                nro: $this->exigir($req->endereco->numero, 'nro'),
                xBairro: $this->exigir($req->endereco->bairro, 'xBairro'),
                xCpl: $req->endereco->complemento,
            );
        }

        if ($req->identificacaoEvento === null && $endereco === null) {
            throw new \InvalidArgumentException('Atividade/Evento deve informar identificacaoEvento ou endereco');
        }

        return new \Hyperdevs\Nfse\Domain\Entity\AtvEvento(
            descricao: $this->exigir($req->descricao, 'xNome (atvEvento)'),
            dataInicio: new \DateTimeImmutable($this->exigir($req->dataInicio, 'dtIni')),
            dataFim: new \DateTimeImmutable($this->exigir($req->dataFim, 'dtFim')),
            identificacaoEvento: $req->identificacaoEvento,
            endereco: $endereco,
        );
    }

    private function criarInfoCompl(?\Hyperdevs\Nfse\Application\DTO\Request\InfoComplRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\InfoCompl
    {
        if ($req === null) {
            return null;
        }

        return new \Hyperdevs\Nfse\Domain\Entity\InfoCompl(
            idDocTecnico: $req->idDocTecnico,
            docReferencia: $req->docReferencia,
            numeroPedido: $req->numeroPedido,
            itensPedido: $req->itensPedido,
            infoComplementar: $req->infoComplementar,
        );
    }

    /**
     * @param \Hyperdevs\Nfse\Application\DTO\Request\DocDedRedRequest[]|null $reqs
     * @return array<int, \Hyperdevs\Nfse\Domain\Entity\DocDedRed>|null
     */
    private function criarDocumentosDeducao(?array $reqs): ?array
    {
        if ($reqs === null) {
            return null;
        }

        // Campos obrigatórios do XSD (TCDocDedRed): a lib não presume tipo de documento,
        // tipo de dedução, data nem valores fiscais. O DpsValidator já rejeita ausências.
        return array_map(
            fn ($d) => new \Hyperdevs\Nfse\Domain\Entity\DocDedRed(
                tipoDocumento: $this->exigir($d->tipoDocumento, 'tipoDocumento (docDedRed)'),
                chaveNFSe: $d->chaveNFSe,
                chaveNFe: $d->chaveNFe,
                codigoMunicipioNFSe: $d->codigoMunicipioNFSe,
                numeroNFSe: $d->numeroNFSe,
                codigoVerificacaoNFSe: $d->codigoVerificacaoNFSe,
                numeroNFS: $d->numeroNFS,
                modeloNFS: $d->modeloNFS,
                serieNFS: $d->serieNFS,
                numeroDocFiscal: $d->numeroDocFiscal,
                numeroDoc: $d->numeroDoc,
                tipoDeducaoReducao: $this->exigir($d->tipoDeducaoReducao, 'tpDedRed'),
                descricaoOutrasDeducoes: $d->descricaoOutrasDeducoes,
                dataEmissaoDoc: new \DateTimeImmutable($this->exigir($d->dataEmissaoDoc, 'dtEmiDoc')),
                valorDedutivel: $this->exigir($d->valorDedutivel, 'vDedutivelRedutivel'),
                valorDeducao: $this->exigir($d->valorDeducao, 'vDeducaoReducao'),
                fornecedor: $d->fornecedor !== null
                    ? new \Hyperdevs\Nfse\Domain\Entity\IbsCbsFornecedor(
                        cnpj: $d->fornecedor->cnpj !== null ? new Cnpj($d->fornecedor->cnpj) : null,
                        cpf: $d->fornecedor->cpf !== null ? new Cpf($d->fornecedor->cpf) : null,
                        nif: $d->fornecedor->nif !== null ? new \Hyperdevs\Nfse\Domain\ValueObject\Nif($d->fornecedor->nif) : null,
                        codigoNaoNif: $d->fornecedor->codigoNaoNif,
                        xNome: $d->fornecedor->xNome,
                    )
                    : null,
            ),
            $reqs,
        );
    }

    private function criarExigSusp(?\Hyperdevs\Nfse\Application\DTO\Request\ExigSuspRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\ExigSusp
    {
        if ($req === null) {
            return null;
        }

        return new \Hyperdevs\Nfse\Domain\Entity\ExigSusp(
            tipoSuspensao: $req->tipoSuspensao,
            numeroProcesso: $req->numeroProcesso,
        );
    }

    private function criarBeneficioMunicipal(?\Hyperdevs\Nfse\Application\DTO\Request\BeneficioMunicipalRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\BeneficioMunicipal
    {
        if ($req === null) {
            return null;
        }

        return new \Hyperdevs\Nfse\Domain\Entity\BeneficioMunicipal(
            numeroBeneficio: $req->numeroBeneficio,
            valorReducaoBC: $req->valorReducaoBC,
            percentualReducaoBC: $req->percentualReducaoBC,
        );
    }

    private function criarTribFederal(?\Hyperdevs\Nfse\Application\DTO\Request\TribFederalRequest $req): ?\Hyperdevs\Nfse\Domain\Entity\TribFederal
    {
        if ($req === null) {
            return null;
        }

        return new \Hyperdevs\Nfse\Domain\Entity\TribFederal(
            pisCofinsCst: $req->pisCofinsCst,
            pisCofinsTipo: $req->pisCofinsTipo,
            pisCofinsAliquotaPis: $req->pisCofinsAliquotaPis,
            pisCofinsAliquotaCofins: $req->pisCofinsAliquotaCofins,
            valorRetidoCP: $req->valorRetidoCP,
            valorRetidoIRRF: $req->valorRetidoIRRF,
            valorRetidoCSLL: $req->valorRetidoCSLL,
            pisCofinsBaseCalculo: $req->pisCofinsBaseCalculo,
            valorPis: $req->valorPis,
            valorCofins: $req->valorCofins,
        );
    }

    /**
     * Constrói o Endereco de tomador/intermediário apenas quando algum dado de endereço
     * foi informado. O grupo <end> é opcional no XSD (TCInfoPessoa/end, minOccurs=0); na
     * ausência total de dados retorna null para que o builder omita o grupo.
     */
    private function criarEnderecoPessoa(
        ?string $logradouro,
        ?string $numero,
        ?string $complemento,
        ?string $bairro,
        ?string $codigoMunicipio,
        ?string $cep,
        ?string $codigoPais = null,
        ?string $codigoPostalExterior = null,
        ?string $nomeCidadeExterior = null,
        ?string $estadoProvinciaExterior = null,
    ): ?Endereco {
        $temEndereco = ($logradouro !== null && $logradouro !== '')
            || ($cep !== null && $cep !== '')
            || ($codigoPais !== null && $codigoPais !== '');

        if (!$temEndereco) {
            return null;
        }

        $ehExterior = $codigoPais !== null;

        return new Endereco(
            logradouro: $this->exigir($logradouro, 'xLgr'),
            numero: $this->exigir($numero, 'nro'),
            complemento: $complemento,
            bairro: $this->exigir($bairro, 'xBairro'),
            codigoMunicipio: $ehExterior ? null : new CodigoMunicipio($this->exigir($codigoMunicipio, 'cMun')),
            cep: $ehExterior ? null : new Cep($this->exigir($cep, 'CEP')),
            codigoPais: $codigoPais,
            codigoPostalExterior: $codigoPostalExterior,
            nomeCidadeExterior: $nomeCidadeExterior,
            estadoProvinciaExterior: $estadoProvinciaExterior,
        );
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processarResposta(array $response, Dps $dps): NfseResponse
    {
        if (!$response['success']) {
            return new NfseResponse(
                success: false,
                mensagem: $this->extrairMensagemErro($response['data'] ?? null, 'Erro ao emitir DPS'),
                dados: is_array($response['data']) ? $response['data'] : null,
                erros: $this->extrairErros($response['data'] ?? null),
            );
        }

        $data = $response['data'];
        $xmlParsed = null;

        if (is_string($data) && !empty($data)) {
            try {
                $parsedList = $this->nfseXmlParser->parse($data);
                if (!empty($parsedList)) {
                    $xmlParsed = $parsedList[0];

                    if ($dps->getIbscbs() !== null) {
                        // A DPS enviou IBS/CBS: a resposta DEVE trazer o grupo IBSCBS. Ausência
                        // (chave presente com valor null, ou ausente) é divergência, não silêncio.
                        $respIbscbs = $xmlParsed['ibscbs'] ?? null;
                        if (!is_array($respIbscbs)) {
                            throw new ServiceException(
                                'Resposta da SEFIN não contém o grupo IBSCBS apesar de a DPS tê-lo enviado'
                            );
                        }
                        $this->ibscbsResponseValidator->validate(
                            $this->buildIbsDataFromDps($dps),
                            $respIbscbs,
                        );
                    }
                }
            } catch (\Throwable $e) {
                throw new ServiceException("Erro ao processar resposta: {$e->getMessage()}", 0, $e);
            }
        }

        // Prioriza os dados REAIS da NFS-e autorizada (parseados da resposta da SEFIN):
        // a chave de acesso definitiva e o número da NFS-e (nNFSe). A chave gerada
        // localmente a partir do DPS é apenas fallback quando a resposta não traz XML.
        $chaveReal = null;
        $numeroNfse = null;
        if (is_array($xmlParsed)) {
            // O atributo Id do infNFSe segue TSIdNFSe: "NFS" + 50 dígitos (53 posições).
            // Só extrai a chave (os 50 dígitos) quando o formato confere — id malformado
            // não vira chave inválida silenciosa; cai no fallback.
            $id = $xmlParsed['id'] ?? null;
            if (is_string($id) && preg_match('/^NFS([0-9]{50})$/', $id, $m) === 1) {
                $chaveReal = $m[1];
            }
            $numero = $xmlParsed['numero'] ?? null;
            $numeroNfse = is_string($numero) && $numero !== '' ? $numero : null;
        }

        return new NfseResponse(
            success: true,
            chaveAcesso: $chaveReal ?? $dps->getChaveAcesso()?->getChave(),
            numero: $numeroNfse,
            dados: is_array($data) ? $data : null,
            xml: is_string($data) ? $data : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIbsDataFromDps(Dps $dps): array
    {
        $ibscbs = $dps->getIbscbs();
        if ($ibscbs === null) {
            return [];
        }

        $data = [
            'tpEnteGov' => $ibscbs->getTpEnteGov()?->value,
            'cClassTrib' => $ibscbs->getCClassTrib()->getCodigo(),
            'cCredPres' => $ibscbs->getCCredPres()?->getCodigo(),
            'vServ' => (string) $dps->getServico()->getValorTotal()->getValue(),
        ];

        if ($ibscbs->getDiferimento() !== null) {
            $data['diferimento'] = [
                'pDifUF' => $ibscbs->getDiferimento()->getPDifUF(),
                'pDifMun' => $ibscbs->getDiferimento()->getPDifMun(),
                'pDifCBS' => $ibscbs->getDiferimento()->getPDifCBS(),
            ];
        }

        if ($ibscbs->hasRefNFSe()) {
            $refList = $ibscbs->getRefNFSeList();
            if ($refList !== null) {
                $data['refNFSeList'] = array_map(
                    fn ($chave) => $chave->getChave(),
                    $refList,
                );
            }
        }

        return $data;
    }
}
