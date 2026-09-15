<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\Validator;

use Hyperevs\Nfse\Application\DTO\Request\DpsRequest;
use Hyperevs\Nfse\Application\DTO\Request\IbsCbsFornecedorRequest;
use Hyperevs\Nfse\Application\DTO\Request\IbsCbsRequest;
use Hyperevs\Nfse\Application\DTO\Request\ServicoRequest;
use Hyperevs\Nfse\Application\Exception\ValidationException;
use Hyperevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperevs\Nfse\Domain\Enum\CausaNaoNif;
use Hyperevs\Nfse\Domain\Enum\EnviarMdic;
use Hyperevs\Nfse\Domain\Enum\FinalidadeNfse;
use Hyperevs\Nfse\Domain\Enum\IndicadorDestinacao;
use Hyperevs\Nfse\Domain\Enum\IndicadorFinal;
use Hyperevs\Nfse\Domain\Enum\MecanismoApoioPrestador;
use Hyperevs\Nfse\Domain\Enum\MecanismoApoioTomador;
use Hyperevs\Nfse\Domain\Enum\ModoPrestacao;
use Hyperevs\Nfse\Domain\Enum\MotivoEmissaoTI;
use Hyperevs\Nfse\Domain\Enum\MotivoSubstituicao;
use Hyperevs\Nfse\Domain\Enum\MovimentacaoTemporaria;
use Hyperevs\Nfse\Domain\Enum\RegimeEspecialTributacao;
use Hyperevs\Nfse\Domain\Enum\RegimeTributario;
use Hyperevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperevs\Nfse\Domain\Enum\TipoChaveDocumentoFiscal;
use Hyperevs\Nfse\Domain\Enum\TipoEmitente;
use Hyperevs\Nfse\Domain\Enum\TipoEnteGovernamental;
use Hyperevs\Nfse\Domain\Enum\TipoOperacao;
use Hyperevs\Nfse\Domain\Enum\TipoReembolsoRepasseRessarcimento;
use Hyperevs\Nfse\Domain\Enum\TipoRetencaoIssqn;
use Hyperevs\Nfse\Domain\Enum\TributacaoIssqn;
use Hyperevs\Nfse\Domain\Enum\VinculoPrestador;

class DpsValidator
{
    private const UFS = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

    /** Domínio oficial do CST de PIS/COFINS (TSTipoCST), conforme NT-007. */
    private const CST_PISCOFINS = [
        '00','01','02','03','04','05','06','07','08','09','49',
        '50','51','52','53','54','55','56',
        '60','61','62','63','64','65','66','67',
        '70','71','72','73','74','75','98','99',
    ];

    public function __construct(
        private ?CstClassTribRepository $cstClassTribRepository = null,
    ) {
    }

    public function validate(DpsRequest $request): void
    {
        $errors = [];

        $this->validateInfDps($request, $errors);
        $this->validatePrestador($request, $errors);
        $this->validateTomador($request, $errors);
        $this->validateIntermediario($request, $errors);
        $this->validateServico($request, $errors);
        $this->validateIbsCbs($request, $errors);
        $this->validateSubstituicao($request, $errors);

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }

    /** @param array<string> $errors */
    private function validateInfDps(DpsRequest $request, array &$errors): void
    {
        if (!in_array($request->tipoAmbiente, TipoAmbiente::valores(), true)) {
            $errors[] = 'tpAmb inválido — deve ser 1 (Produção) ou 2 (Homologação)';
        }

        if (!in_array($request->tipoEmissao, TipoEmitente::valores(), true)) {
            $errors[] = 'tpEmit inválido — deve ser 1 (Prestador), 2 (Tomador) ou 3 (Intermediário) (TSEmitenteDPS)';
        }

        if ($request->cMotivoEmisTI !== null && !in_array($request->cMotivoEmisTI, MotivoEmissaoTI::valores(), true)) {
            $errors[] = 'cMotivoEmisTI inválido (TSMotivoEmisTI)';
        }

        $serieStr = sprintf('%05d', $request->serie);
        if (!preg_match('/^0{0,4}\d{1,5}$/', $serieStr)) {
            $errors[] = 'serie deve ser numérico de 1 a 5 dígitos (TSSerieDPS)';
        }

        $nDpsStr = (string) $request->numero;
        if (!preg_match('/^[1-9][0-9]{0,14}$/', $nDpsStr)) {
            $errors[] = 'nDPS deve ser numérico de 1 a 15 dígitos, sem leading zeros (TSNumDPS)';
        }

        if (empty($request->versaoAplicacao)) {
            $errors[] = 'verAplic é obrigatória';
        } elseif (strlen($request->versaoAplicacao) > 20) {
            $errors[] = 'verAplic deve ter no máximo 20 caracteres (TSVerAplic)';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\-+]\d{2}:\d{2}$/', $request->dataEmissao)) {
            $errors[] = 'dhEmi deve estar no formato AAAA-MM-DDTHH:MM:SS±HH:MM (TSDateTimeUTC)';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->dataCompetencia)) {
            $errors[] = 'dCompet deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (!preg_match('/^[0-9]{7}$/', $request->codigoMunicipioEmissor)) {
            $errors[] = 'cLocEmi deve ter exatamente 7 dígitos numéricos (TSCodMunIBGE)';
        }

        if ($request->chNFSeRej !== null && !preg_match('/^[0-9]{50}$/', $request->chNFSeRej)) {
            $errors[] = 'chNFSeRej deve ter exatamente 50 dígitos numéricos (TSChaveNFSe)';
        }
    }

    /** @param array<string> $errors */
    private function validatePrestador(DpsRequest $request, array &$errors): void
    {
        $p = $request->prestador;

        if (!in_array($p->regimeTributario, RegimeTributario::valores(), true)) {
            $errors[] = 'opSimpNac inválido — deve ser 1, 2 ou 3 (TSOpSimpNac)';
        }

        if ($p->regApTribSN !== null && !in_array($p->regApTribSN, [1, 2, 3], true)) {
            $errors[] = 'regApTribSN inválido — deve ser 1, 2 ou 3 (TSRegimeApuracaoSimpNac)';
        }

        if ($p->regEspTrib !== null && !in_array((string) $p->regEspTrib, RegimeEspecialTributacao::valores(), true)) {
            $errors[] = 'regEspTrib inválido — deve ser 0, 1, 2, 3, 4, 5, 6 ou 9 (TSRegEspTrib)';
        }

        if (strlen($p->razaoSocial) > 300) {
            $errors[] = 'xNome do prestador deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }

        $idCount = 0;
        if ($p->documento !== null) {
            $idCount++;
        }
        if ($p->nif !== null) {
            $idCount++;
        }
        if ($p->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Prestador deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Prestador: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($p->documento !== null) {
            if ($p->isCnpj === true) {
                if (!preg_match('/^[0-9]{14}$/', $p->documento)) {
                    $errors[] = 'CNPJ do prestador deve ter 14 dígitos numéricos (TSCNPJ)';
                }
            } elseif ($p->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $p->documento)) {
                    $errors[] = 'CPF do prestador deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($p->nif !== null && (strlen($p->nif) < 1 || strlen($p->nif) > 40)) {
            $errors[] = 'NIF do prestador deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($p->codigoNaoNif !== null && !in_array($p->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF inválido — deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($p->caepf !== null && !preg_match('/^[0-9]{14}$/', $p->caepf)) {
            $errors[] = 'CAEPF do prestador deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($p->inscricaoMunicipal !== null && (strlen($p->inscricaoMunicipal) < 1 || strlen($p->inscricaoMunicipal) > 15)) {
            $errors[] = 'IM do prestador deve ter 1 a 15 caracteres (TSInscMun)';
        }

        // cMun NÃO faz parte do grupo <end>: alimenta cLocEmi e é sempre obrigatório.
        if (!preg_match('/^[0-9]{7}$/', $p->codigoMunicipio)) {
            $errors[] = 'cMun do prestador deve ter 7 dígitos numéricos (TSCodMunIBGE)';
        }


        $temEndereco = $p->logradouro !== null
            || $p->numero !== null
            || $p->complemento !== null
            || $p->bairro !== null
            || $p->uf !== null
            || $p->cep !== null;

        if ($temEndereco) {
            if (!preg_match('/^[0-9]{8}$/', (string) $p->cep)) {
                $errors[] = 'CEP do prestador deve ter 8 dígitos numéricos (TSCEP)';
            }

            if (empty($p->logradouro)) {
                $errors[] = 'xLgr do prestador é obrigatório quando o endereço é informado (TSLogradouro)';
            } elseif (strlen($p->logradouro) > 255) {
                $errors[] = 'xLgr do prestador deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($p->numero)) {
                $errors[] = 'nro do prestador é obrigatório quando o endereço é informado (TSNumeroEndereco)';
            } elseif (strlen($p->numero) > 60) {
                $errors[] = 'nro do prestador deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($p->complemento !== null && (strlen($p->complemento) < 1 || strlen($p->complemento) > 156)) {
                $errors[] = 'xCpl do prestador deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($p->bairro)) {
                $errors[] = 'xBairro do prestador é obrigatório quando o endereço é informado (TSBairro)';
            } elseif (strlen($p->bairro) > 60) {
                $errors[] = 'xBairro do prestador deve ter no máximo 60 caracteres (TSBairro)';
            }

            if (!in_array($p->uf, self::UFS, true)) {
                $errors[] = 'UF do prestador inválida (TSUF)';
            }
        }

        if ($p->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $p->telefone)) {
            $errors[] = 'fone do prestador deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($p->email !== null && (strlen($p->email) < 1 || strlen($p->email) > 80)) {
            $errors[] = 'email do prestador deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateTomador(DpsRequest $request, array &$errors): void
    {
        $t = $request->tomador;
        if ($t === null) {
            return;
        }

        if (empty($t->razaoSocial)) {
            $errors[] = 'xNome do tomador é obrigatória (TSNomeRazaoSocial)';
        } elseif (strlen($t->razaoSocial) > 300) {
            $errors[] = 'xNome do tomador deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }


        $idCount = 0;
        if ($t->documento !== null) {
            $idCount++;
        }
        if ($t->nif !== null) {
            $idCount++;
        }
        if ($t->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Tomador deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Tomador: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($t->documento !== null) {
            if ($t->isCnpj === true) {
                if (!preg_match('/^[0-9]{14}$/', $t->documento)) {
                    $errors[] = 'CNPJ do tomador deve ter 14 dígitos numéricos (TSCNPJ)';
                }
            } elseif ($t->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $t->documento)) {
                    $errors[] = 'CPF do tomador deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($t->nif !== null && (strlen($t->nif) < 1 || strlen($t->nif) > 40)) {
            $errors[] = 'NIF do tomador deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($t->codigoNaoNif !== null && !in_array($t->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do tomador inválido — deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($t->caepf !== null && !preg_match('/^[0-9]{14}$/', $t->caepf)) {
            $errors[] = 'CAEPF do tomador deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($t->inscricaoMunicipal !== null && (strlen($t->inscricaoMunicipal) < 1 || strlen($t->inscricaoMunicipal) > 15)) {
            $errors[] = 'IM do tomador deve ter 1 a 15 caracteres (TSInscMun)';
        }

        // <end> é opcional (TCInfoPessoa/end, minOccurs=0). Só validamos quando algum
        // dado de endereço foi informado. codigoPais setado => endereço exterior (endExt).
        $tomadorExterior = $t->codigoPais !== null;

        $hasAddress = !empty($t->codigoMunicipio) || !empty($t->cep) || !empty($t->logradouro)
            || !empty($t->numero) || !empty($t->complemento) || !empty($t->bairro) || !empty($t->uf)
            || $tomadorExterior;

        if ($hasAddress) {
            // xLgr, nro e xBairro são obrigatórios (minOccurs=1) dentro de <end> — não podem
            // ficar vazios quando o grupo é emitido, senão o XSD rejeita com erro genérico.
            if (empty($t->logradouro)) {
                $errors[] = 'xLgr do tomador é obrigatório quando há endereço (TSLogradouro)';
            } elseif (strlen($t->logradouro) > 255) {
                $errors[] = 'xLgr do tomador deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($t->numero)) {
                $errors[] = 'nro do tomador é obrigatório quando há endereço — use "S/N" se não houver (TSNumeroEndereco)';
            } elseif (strlen($t->numero) > 60) {
                $errors[] = 'nro do tomador deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($t->complemento !== null && (strlen($t->complemento) < 1 || strlen($t->complemento) > 156)) {
                $errors[] = 'xCpl do tomador deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($t->bairro)) {
                $errors[] = 'xBairro do tomador é obrigatório quando há endereço (TSBairro)';
            } elseif (strlen($t->bairro) > 60) {
                $errors[] = 'xBairro do tomador deve ter no máximo 60 caracteres (TSBairro)';
            }

            if ($tomadorExterior) {
                // endExt: cPais, cEndPost, xCidade, xEstProvReg são todos obrigatórios (minOccurs=1).
                if (!preg_match('/^[A-Z]{2}$/', $t->codigoPais)) {
                    $errors[] = 'cPais do tomador deve ser 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($t->codigoPostalExterior === null || strlen($t->codigoPostalExterior) < 1 || strlen($t->codigoPostalExterior) > 11) {
                    $errors[] = 'cEndPost do tomador é obrigatório e deve ter 1 a 11 caracteres para endereço exterior (TSCodigoEndPostal)';
                }
                if ($t->nomeCidadeExterior === null || strlen($t->nomeCidadeExterior) < 1 || strlen($t->nomeCidadeExterior) > 60) {
                    $errors[] = 'xCidade do tomador é obrigatória e deve ter 1 a 60 caracteres para endereço exterior (TSCidade)';
                }
                if ($t->estadoProvinciaExterior === null || strlen($t->estadoProvinciaExterior) < 1 || strlen($t->estadoProvinciaExterior) > 60) {
                    $errors[] = 'xEstProvReg do tomador é obrigatório e deve ter 1 a 60 caracteres para endereço exterior (TSEstadoProvRegiao)';
                }
            } else {
                // endNac: cMun, CEP, UF obrigatórios.
                if ($t->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $t->codigoMunicipio)) {
                    $errors[] = 'cMun do tomador deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($t->cep === null || !preg_match('/^[0-9]{8}$/', $t->cep)) {
                    $errors[] = 'CEP do tomador deve ter 8 dígitos numéricos (TSCEP)';
                }
                if (!in_array($t->uf, self::UFS, true)) {
                    $errors[] = 'UF do tomador inválida (TSUF)';
                }
            }
        }

        if ($t->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $t->telefone)) {
            $errors[] = 'fone do tomador deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($t->email !== null && (strlen($t->email) < 1 || strlen($t->email) > 80)) {
            $errors[] = 'email do tomador deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateIntermediario(DpsRequest $request, array &$errors): void
    {
        $i = $request->intermediario;
        if ($i === null) {
            return;
        }

        if (empty($i->razaoSocial)) {
            $errors[] = 'xNome do intermediário é obrigatória (TSNomeRazaoSocial)';
        } elseif (strlen($i->razaoSocial) > 300) {
            $errors[] = 'xNome do intermediário deve ter no máximo 300 caracteres (TSNomeRazaoSocial)';
        }

        $idCount = 0;
        if ($i->documento !== null) {
            $idCount++;
        }
        if ($i->nif !== null) {
            $idCount++;
        }
        if ($i->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Intermediário deve ter CNPJ, CPF, NIF ou cNaoNIF';
        }
        if ($idCount > 1) {
            $errors[] = 'Intermediário: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($i->documento !== null) {
            if ($i->isCnpj === true) {
                if (!preg_match('/^[0-9]{14}$/', $i->documento)) {
                    $errors[] = 'CNPJ do intermediário deve ter 14 dígitos numéricos (TSCNPJ)';
                }
            } elseif ($i->isCnpj === false) {
                if (!preg_match('/^[0-9]{11}$/', $i->documento)) {
                    $errors[] = 'CPF do intermediário deve ter 11 dígitos numéricos (TSCPF)';
                }
            }
        }

        if ($i->nif !== null && (strlen($i->nif) < 1 || strlen($i->nif) > 40)) {
            $errors[] = 'NIF do intermediário deve ter 1 a 40 caracteres (TSNIF)';
        }

        if ($i->codigoNaoNif !== null && !in_array($i->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do intermediário inválido — deve ser 0, 1 ou 2 (TSCodNaoNIF)';
        }

        if ($i->caepf !== null && !preg_match('/^[0-9]{14}$/', $i->caepf)) {
            $errors[] = 'CAEPF do intermediário deve ter 14 dígitos numéricos (TSCAEPF)';
        }

        if ($i->inscricaoMunicipal !== null && (strlen($i->inscricaoMunicipal) < 1 || strlen($i->inscricaoMunicipal) > 15)) {
            $errors[] = 'IM do intermediário deve ter entre 1 e 15 caracteres (TSInscMun)';
        }

        $intermediarioExterior = $i->codigoPais !== null;

        $hasAddress = !empty($i->codigoMunicipio) || !empty($i->cep) || !empty($i->logradouro)
            || !empty($i->numero) || !empty($i->complemento) || !empty($i->bairro) || !empty($i->uf)
            || $intermediarioExterior;

        if ($hasAddress) {
            // xLgr, nro e xBairro são obrigatórios (minOccurs=1) dentro de <end>.
            if (empty($i->logradouro)) {
                $errors[] = 'xLgr do intermediário é obrigatório quando há endereço (TSLogradouro)';
            } elseif (strlen($i->logradouro) > 255) {
                $errors[] = 'xLgr do intermediário deve ter no máximo 255 caracteres (TSLogradouro)';
            }

            if (empty($i->numero)) {
                $errors[] = 'nro do intermediário é obrigatório quando há endereço — use "S/N" se não houver (TSNumeroEndereco)';
            } elseif (strlen($i->numero) > 60) {
                $errors[] = 'nro do intermediário deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }

            if ($i->complemento !== null && strlen($i->complemento) > 156) {
                $errors[] = 'xCpl do intermediário deve ter no máximo 156 caracteres (TSComplementoEndereco)';
            }

            if (empty($i->bairro)) {
                $errors[] = 'xBairro do intermediário é obrigatório quando há endereço (TSBairro)';
            } elseif (strlen($i->bairro) > 60) {
                $errors[] = 'xBairro do intermediário deve ter no máximo 60 caracteres (TSBairro)';
            }

            // TCEndereco é choice endNac | endExt.
            if ($intermediarioExterior) {
                if (!preg_match('/^[A-Z]{2}$/', $i->codigoPais)) {
                    $errors[] = 'cPais do intermediário deve ser 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($i->codigoPostalExterior === null || strlen($i->codigoPostalExterior) < 1 || strlen($i->codigoPostalExterior) > 11) {
                    $errors[] = 'cEndPost do intermediário é obrigatório e deve ter 1 a 11 caracteres para endereço exterior (TSCodigoEndPostal)';
                }
                if ($i->nomeCidadeExterior === null || strlen($i->nomeCidadeExterior) < 1 || strlen($i->nomeCidadeExterior) > 60) {
                    $errors[] = 'xCidade do intermediário é obrigatória e deve ter 1 a 60 caracteres para endereço exterior (TSCidade)';
                }
                if ($i->estadoProvinciaExterior === null || strlen($i->estadoProvinciaExterior) < 1 || strlen($i->estadoProvinciaExterior) > 60) {
                    $errors[] = 'xEstProvReg do intermediário é obrigatório e deve ter 1 a 60 caracteres para endereço exterior (TSEstadoProvRegiao)';
                }
            } else {
                if ($i->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $i->codigoMunicipio)) {
                    $errors[] = 'cMun do intermediário deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($i->cep === null || !preg_match('/^[0-9]{8}$/', $i->cep)) {
                    $errors[] = 'CEP do intermediário deve ter 8 dígitos numéricos (TSCEP)';
                }
                if (!in_array($i->uf, self::UFS, true)) {
                    $errors[] = 'UF do intermediário inválida (TSUF)';
                }
            }
        }

        if ($i->telefone !== null && !preg_match('/^[0-9]{6,20}$/', $i->telefone)) {
            $errors[] = 'fone do intermediário deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }

        if ($i->email !== null && (strlen($i->email) < 1 || strlen($i->email) > 80)) {
            $errors[] = 'email do intermediário deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateServico(DpsRequest $request, array &$errors): void
    {
        $s = $request->servico;

        if (empty($s->discriminacao)) {
            $errors[] = 'xDescServ é obrigatória (TSDesc2000)';
        } elseif (strlen($s->discriminacao) > 2000) {
            $errors[] = 'xDescServ deve ter no máximo 2000 caracteres (TSDesc2000)';
        }

        if (empty($s->codigoTributacao)) {
            $errors[] = 'cTribNac é obrigatório (TSCodTribNac)';
        } elseif (!preg_match('/^[0-9]{6}$/', $s->codigoTributacao)) {
            $errors[] = 'cTribNac deve ter 6 dígitos numéricos (TSCodTribNac)';
        }

        if ($s->codigoTributacaoMunicipal !== null && !preg_match('/^[0-9]{3}$/', $s->codigoTributacaoMunicipal)) {
            $errors[] = 'cTribMun deve ter 3 dígitos numéricos (TCCodTribMun)';
        }

        if ($s->codigoNbs !== null && !preg_match('/^[0-9]{9}$/', $s->codigoNbs)) {
            $errors[] = 'cNBS deve ter 9 dígitos numéricos (TSCodNBS)';
        }

        if ($s->codigoInternoContribuinte !== null && !preg_match('/^[a-zA-Z0-9]{1,20}$/', $s->codigoInternoContribuinte)) {
            $errors[] = 'cIntContrib deve ser alfanumérico de 1 a 20 caracteres (TSCodigoInternoContribuinte)';
        }

        if ($s->valorServicos <= 0) {
            $errors[] = 'vServ deve ser maior que zero (TSDec15V2)';
        }

        if ($s->descontoIncondicionado !== null && $s->descontoIncondicionado < 0) {
            $errors[] = 'vDescIncond não pode ser negativo (TSDec15V2)';
        }

        if ($s->descontoCondicionado !== null && $s->descontoCondicionado < 0) {
            $errors[] = 'vDescCond não pode ser negativo (TSDec15V2)';
        }

        $totalDescontos = ($s->descontoIncondicionado ?? 0.0) + ($s->descontoCondicionado ?? 0.0);
        if ($s->valorServicos > 0 && $totalDescontos >= $s->valorServicos) {
            $errors[] = 'A soma de vDescIncond e vDescCond deve ser menor que vServ (vTotal deve ser positivo)';
        }

        // pAliq é TSDec1V2: 1 dígito inteiro + 2 decimais → máximo 9.99 (cobre o teto legal de ISS de 5%).
        if ($s->aliquotaIss < 0 || $s->aliquotaIss > 9.99) {
            $errors[] = 'pAliq deve estar entre 0 e 9.99 (TSDec1V2)';
        }

        if ($s->valorRecebido !== null && $s->valorRecebido <= 0) {
            $errors[] = 'vReceb deve ser maior que zero (TSDec15V2)';
        }

        $this->validateLocPrest($s, $errors);
        $this->validateObra($s, $errors);
        $this->validateComExterior($s, $errors);
        $this->validateAtvEvento($s, $errors);
        $this->validateInfoCompl($s, $request->tipoEmissao, $errors);
        $this->validateDocumentosDeducao($s, $errors);
        $this->validateTribMun($s, $errors);
        $this->validateTribFed($s, $errors);
        $this->validateTotTrib($s, $errors);
    }

    /** @param array<string> $errors */
    private function validateLocPrest(ServicoRequest $s, array &$errors): void
    {
        $hasLoc = $s->codigoMunicipioPrestacao !== null && $s->codigoMunicipioPrestacao !== '';
        $hasPais = $s->codigoPaisPrestacao !== null && $s->codigoPaisPrestacao !== '';

        if (!$hasLoc && !$hasPais) {
            $errors[] = 'É obrigatório informar cLocPrestacao ou cPaisPrestacao (TCLocPrestacao choice)';
        }
        if ($hasLoc && $hasPais) {
            $errors[] = 'cLocPrestacao e cPaisPrestacao são mutuamente exclusivos (TCLocPrestacao choice)';
        }

        if ($hasLoc && !preg_match('/^[0-9]{7}$/', $s->codigoMunicipioPrestacao)) {
            $errors[] = 'cLocPrestacao deve ter 7 dígitos numéricos (TSCodMunIBGE)';
        }

        if ($hasPais && !preg_match('/^[A-Z]{2}$/', $s->codigoPaisPrestacao)) {
            $errors[] = 'cPaisPrestacao deve ser 2 letras maiúsculas (TSCodPaisISO)';
        }
    }

    /** @param array<string> $errors */
    private function validateObra(ServicoRequest $s, array &$errors): void
    {
        $o = $s->obra;
        if ($o === null) {
            return;
        }

        if ($o->inscImobFisc !== null && (strlen($o->inscImobFisc) < 1 || strlen($o->inscImobFisc) > 30)) {
            $errors[] = 'inscImobFisc deve ter 1 a 30 caracteres (TSInscImobFisc)';
        }

        $hasCObra = $o->cObra !== null;
        $hasCCIB = $o->cCIB !== null;
        $hasEnd = $o->endereco !== null;
        $choices = ($hasCObra ? 1 : 0) + ($hasCCIB ? 1 : 0) + ($hasEnd ? 1 : 0);

        if ($choices === 0) {
            $errors[] = 'É obrigatório informar cObra, cCIB ou end no grupo obra (TCInfoObra choice)';
        }
        if ($choices > 1) {
            $errors[] = 'cObra, cCIB e end são mutuamente exclusivos no grupo obra (TCInfoObra choice)';
        }

        if ($hasCObra && (strlen($o->cObra) < 1 || strlen($o->cObra) > 30)) {
            $errors[] = 'cObra deve ter 1 a 30 caracteres (TSCodObra)';
        }

        if ($hasCCIB && mb_strlen($o->cCIB) !== 8) {
            $errors[] = 'cCIB deve ter exatamente 8 caracteres (TSCodCIB)';
        }

        if ($hasEnd) {
            $e = $o->endereco;

            // TCEnderObraEvento abre com xs:choice obrigatória CEP|endExt (exatamente um).
            if ($e->cep !== null && $e->endExt !== null) {
                $errors[] = 'CEP e endExt são mutuamente exclusivos no endereço da obra (choice)';
            }
            if ($e->cep === null && $e->endExt === null) {
                $errors[] = 'É obrigatório informar CEP ou endExt no endereço da obra (choice)';
            }

            if ($e->cep !== null && !preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = 'CEP do endereço da obra deve ter 8 dígitos numéricos (TSCEP)';
            }

            if ($e->endExt !== null) {
                if (strlen($e->endExt->cEndPost) < 1 || strlen($e->endExt->cEndPost) > 11) {
                    $errors[] = 'cEndPost do endExt da obra deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if (strlen($e->endExt->xCidade) < 1 || strlen($e->endExt->xCidade) > 60) {
                    $errors[] = 'xCidade do endExt da obra deve ter 1 a 60 caracteres (TSCidade)';
                }
                if (strlen($e->endExt->xEstProvReg) < 1 || strlen($e->endExt->xEstProvReg) > 60) {
                    $errors[] = 'xEstProvReg do endExt da obra deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            }

            if (empty($e->xLgr)) {
                $errors[] = 'xLgr é obrigatório no endereço da obra (TSLogradouro)';
            } elseif (strlen($e->xLgr) > 255) {
                $errors[] = 'xLgr deve ter no máximo 255 caracteres (TSLogradouro)';
            }
            if (empty($e->nro)) {
                $errors[] = 'nro é obrigatório no endereço da obra (TSNumeroEndereco)';
            } elseif (strlen($e->nro) > 60) {
                $errors[] = 'nro deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }
            if ($e->xCpl !== null && (strlen($e->xCpl) < 1 || strlen($e->xCpl) > 156)) {
                $errors[] = 'xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if (empty($e->xBairro)) {
                $errors[] = 'xBairro é obrigatório no endereço da obra (TSBairro)';
            } elseif (strlen($e->xBairro) > 60) {
                $errors[] = 'xBairro deve ter no máximo 60 caracteres (TSBairro)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateComExterior(ServicoRequest $s, array &$errors): void
    {
        $ce = $s->comExterior;
        if ($ce === null) {
            return;
        }

        if ($ce->modoPrestacao === null) {
            $errors[] = 'mdPrestacao é obrigatório (TSModoPrestacao)';
        } elseif (!in_array((string) $ce->modoPrestacao, ModoPrestacao::valores(), true)) {
            $errors[] = 'mdPrestacao inválido (TSModoPrestacao)';
        }

        if ($ce->vinculoPrestador === null) {
            $errors[] = 'vincPrest é obrigatório (TSVincPrest)';
        } elseif (!in_array((string) $ce->vinculoPrestador, VinculoPrestador::valores(), true)) {
            $errors[] = 'vincPrest inválido (TSVincPrest)';
        }

        if ($ce->codigoMoeda === null) {
            $errors[] = 'tpMoeda é obrigatório (TSCodMoeda)';
        } elseif (!preg_match('/^[0-9]{3}$/', $ce->codigoMoeda)) {
            $errors[] = 'tpMoeda deve ter 3 dígitos numéricos (TSCodMoeda)';
        }

        if ($ce->valorServicoMoeda === null) {
            $errors[] = 'vServMoeda é obrigatório (TSDec15V2)';
        } elseif ($ce->valorServicoMoeda <= 0) {
            $errors[] = 'vServMoeda deve ser maior que zero (TSDec15V2)';
        }

        if ($ce->mecanismoApoioPrestador === null) {
            $errors[] = 'mecAFComexP é obrigatório (TSMecAFComExPrest)';
        } elseif (!in_array($ce->mecanismoApoioPrestador, MecanismoApoioPrestador::valores(), true)) {
            $errors[] = 'mecAFComexP inválido (TSMecAFComExPrest)';
        }

        if ($ce->mecanismoApoioTomador === null) {
            $errors[] = 'mecAFComexT é obrigatório (TSMecAFComExToma)';
        } elseif (!in_array($ce->mecanismoApoioTomador, MecanismoApoioTomador::valores(), true)) {
            $errors[] = 'mecAFComexT inválido (TSMecAFComExToma)';
        }

        if ($ce->movimentacaoTemporaria === null) {
            $errors[] = 'movTempBens é obrigatório (TSMovTempBens)';
        } elseif (!in_array($ce->movimentacaoTemporaria, MovimentacaoTemporaria::valores(), true)) {
            $errors[] = 'movTempBens inválido (TSMovTempBens)';
        }

        if ($ce->numeroDeclaracaoImportacao !== null && (strlen($ce->numeroDeclaracaoImportacao) < 1 || strlen($ce->numeroDeclaracaoImportacao) > 12)) {
            $errors[] = 'nDI deve ter 1 a 12 caracteres (TSNumDocImport)';
        }

        if ($ce->numeroRegistroExportacao !== null && (strlen($ce->numeroRegistroExportacao) < 1 || strlen($ce->numeroRegistroExportacao) > 12)) {
            $errors[] = 'nRE deve ter 1 a 12 caracteres (TSNumRegExport)';
        }

        if ($ce->enviarMDIC === null) {
            $errors[] = 'mdic é obrigatório (TSEnvMDIC)';
        } elseif (!in_array($ce->enviarMDIC, EnviarMdic::valores(), true)) {
            $errors[] = 'mdic inválido (TSEnvMDIC)';
        }
    }

    /** @param array<string> $errors */
    private function validateAtvEvento(ServicoRequest $s, array &$errors): void
    {
        $ae = $s->atvEvento;
        if ($ae === null) {
            return;
        }

        if (empty($ae->descricao)) {
            $errors[] = 'xNome é obrigatório no grupo atvEvento (TSDesc255)';
        } elseif (strlen($ae->descricao) > 255) {
            $errors[] = 'xNome deve ter no máximo 255 caracteres (TSDesc255)';
        }

        if (empty($ae->dataInicio)) {
            $errors[] = 'dtIni é obrigatório no grupo atvEvento (TSData)';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataInicio)) {
            $errors[] = 'dtIni deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (empty($ae->dataFim)) {
            $errors[] = 'dtFim é obrigatório no grupo atvEvento (TSData)';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ae->dataFim)) {
            $errors[] = 'dtFim deve estar no formato AAAA-MM-DD (TSData)';
        }

        if (!empty($ae->dataInicio) && !empty($ae->dataFim) && $ae->dataInicio > $ae->dataFim) {
            $errors[] = 'dtIni não pode ser posterior a dtFim';
        }

        $hasId = $ae->identificacaoEvento !== null && $ae->identificacaoEvento !== '';
        $hasEnd = $ae->endereco !== null;

        if (!$hasId && !$hasEnd) {
            $errors[] = 'É obrigatório informar idAtvEvt ou end no grupo atvEvento (TCAtvEvento choice)';
        }
        if ($hasId && $hasEnd) {
            $errors[] = 'idAtvEvt e end são mutuamente exclusivos no grupo atvEvento (TCAtvEvento choice)';
        }

        if ($hasId && strlen($ae->identificacaoEvento) > 30) {
            $errors[] = 'idAtvEvt deve ter 1 a 30 caracteres (TSIdeEvento)';
        }

        if ($hasEnd) {
            $e = $ae->endereco;

            // TCEnderecoSimples abre com xs:choice obrigatória CEP|endExt (exatamente um).
            // O exterior é determinado por codigoPais (vide EmitirDpsService::criarAtvEvento).
            $ehExterior = $e->codigoPais !== null;
            if ($e->cep !== null && $ehExterior) {
                $errors[] = 'CEP e endExt são mutuamente exclusivos no endereço do evento (choice)';
            }
            if ($e->cep === null && !$ehExterior) {
                $errors[] = 'É obrigatório informar CEP ou endExt no endereço do evento (choice)';
            }

            if ($ehExterior) {
                if ($e->codigoPostalExterior === null || strlen($e->codigoPostalExterior) < 1 || strlen($e->codigoPostalExterior) > 11) {
                    $errors[] = 'cEndPost do endExt do evento deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if ($e->nomeCidadeExterior === null || strlen($e->nomeCidadeExterior) < 1 || strlen($e->nomeCidadeExterior) > 60) {
                    $errors[] = 'xCidade do endExt do evento deve ter 1 a 60 caracteres (TSCidade)';
                }
                if ($e->estadoProvinciaExterior === null || strlen($e->estadoProvinciaExterior) < 1 || strlen($e->estadoProvinciaExterior) > 60) {
                    $errors[] = 'xEstProvReg do endExt do evento deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            }

            if ($e->cep !== null && !preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = 'CEP do endereço do evento deve ter 8 dígitos numéricos (TSCEP)';
            }
            if (empty($e->logradouro)) {
                $errors[] = 'xLgr é obrigatório no endereço do evento (TSLogradouro)';
            } elseif (strlen($e->logradouro) > 255) {
                $errors[] = 'xLgr deve ter no máximo 255 caracteres (TSLogradouro)';
            }
            if (empty($e->numero)) {
                $errors[] = 'nro é obrigatório no endereço do evento (TSNumeroEndereco)';
            } elseif (strlen($e->numero) > 60) {
                $errors[] = 'nro deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }
            if ($e->complemento !== null && (strlen($e->complemento) < 1 || strlen($e->complemento) > 156)) {
                $errors[] = 'xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if (empty($e->bairro)) {
                $errors[] = 'xBairro é obrigatório no endereço do evento (TSBairro)';
            } elseif (strlen($e->bairro) > 60) {
                $errors[] = 'xBairro deve ter no máximo 60 caracteres (TSBairro)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateInfoCompl(ServicoRequest $s, int $tipoEmissao, array &$errors): void
    {
        $ic = $s->infoCompl;

        $emitidaPorTomadorOuInterm = in_array($tipoEmissao, [2, 3], true);
        if ($emitidaPorTomadorOuInterm && ($ic === null || $ic->docReferencia === null || $ic->docReferencia === '')) {
            $errors[] = 'docRef é obrigatório quando a NFS-e é emitida pelo Tomador ou Intermediário (tpEmit=2 ou 3)';
        }

        if ($ic === null) {
            return;
        }

        if ($ic->idDocTecnico !== null && (strlen($ic->idDocTecnico) < 1 || strlen($ic->idDocTecnico) > 40)) {
            $errors[] = 'idDocTec deve ter 1 a 40 caracteres (TSDRT)';
        }

        if ($ic->docReferencia !== null && (strlen($ic->docReferencia) < 1 || strlen($ic->docReferencia) > 255)) {
            $errors[] = 'docRef deve ter 1 a 255 caracteres (TSDesc255)';
        }

        if ($ic->numeroPedido !== null && (strlen($ic->numeroPedido) < 1 || strlen($ic->numeroPedido) > 60)) {
            $errors[] = 'xPed deve ter 1 a 60 caracteres (TSNumeroEndereco)';
        }

        if ($ic->itensPedido !== null) {
            if (count($ic->itensPedido) > 99) {
                $errors[] = 'gItemPed deve conter no máximo 99 itens (xItemPed maxOccurs=99)';
            }
            foreach ($ic->itensPedido as $i => $item) {
                if (strlen($item) < 1 || strlen($item) > 60) {
                    $errors[] = "xItemPed #{$i} deve ter 1 a 60 caracteres (TSNumeroEndereco)";
                }
            }
        }

        if ($ic->infoComplementar !== null && (strlen($ic->infoComplementar) < 1 || strlen($ic->infoComplementar) > 2000)) {
            $errors[] = 'xInfComp deve ter 1 a 2000 caracteres (TSDescInfCompl)';
        }
    }

    /** @param array<string> $errors */
    private function validateDocumentosDeducao(ServicoRequest $s, array &$errors): void
    {
        $hasPDR = $s->percentualDeducao !== null;
        $hasVDR = $s->valorDeducaoPadrao !== null;
        $hasDocs = $s->documentosDeducao !== null;

        if ($hasPDR || $hasVDR || $hasDocs) {
            $choices = ($hasPDR ? 1 : 0) + ($hasVDR ? 1 : 0) + ($hasDocs ? 1 : 0);

            if ($choices > 1) {
                $errors[] = 'pDR, vDR e documentos são mutuamente exclusivos (TCInfoDedRed choice)';
            }

            if ($hasPDR && ($s->percentualDeducao < 0 || $s->percentualDeducao > 100)) {
                $errors[] = 'pDR deve estar entre 0 e 100 (TSDec3V2)';
            }

            if ($hasVDR && $s->valorDeducaoPadrao <= 0) {
                $errors[] = 'vDR deve ser maior que zero (TSDec15V2)';
            }

            // A dedução/redução não pode exceder o valor do serviço — geraria
            // base de cálculo do ISS negativa. Vale para os três modos de <vDedRed>.
            if ($hasVDR && $s->valorDeducaoPadrao > $s->valorServicos) {
                $errors[] = 'vDR não pode exceder vServ (base de cálculo negativa)';
            }
            if ($s->documentosDeducao !== null) {
                $somaDeducao = 0.0;
                foreach ($s->documentosDeducao as $doc) {
                    $somaDeducao += (float) ($doc->valorDeducao ?? 0);
                }
                if ($somaDeducao > $s->valorServicos) {
                    $errors[] = 'A soma das deduções (vDeducaoReducao) não pode exceder vServ (base de cálculo negativa)';
                }
            }
        }

        if ($s->documentosDeducao === null) {
            return;
        }

        if (count($s->documentosDeducao) > 1000) {
            $errors[] = 'docDedRed deve conter no máximo 1000 documentos (maxOccurs=1000)';
        }

        $validTypes = ['chNFSe', 'chNFe', 'NFSeMun', 'NFNFS', 'nDocFisc', 'nDoc'];

        foreach ($s->documentosDeducao as $i => $doc) {
            $pfx = "DocDedRed #{$i}";

            if ($doc->tipoDocumento === null || !in_array($doc->tipoDocumento, $validTypes, true)) {
                $errors[] = "{$pfx}: tipoDocumento inválido";
            }

            if ($doc->tipoDocumento === 'chNFSe') {
                if (empty($doc->chaveNFSe)) {
                    $errors[] = "{$pfx}: chNFSe é obrigatória";
                } elseif (!preg_match('/^[0-9]{50}$/', $doc->chaveNFSe)) {
                    $errors[] = "{$pfx}: chNFSe deve ter 50 dígitos numéricos (TSChaveNFSe)";
                }
            }

            if ($doc->tipoDocumento === 'chNFe') {
                if (empty($doc->chaveNFe)) {
                    $errors[] = "{$pfx}: chNFe é obrigatória";
                } elseif (!preg_match('/^[0-9]{44}$/', $doc->chaveNFe)) {
                    $errors[] = "{$pfx}: chNFe deve ter 44 dígitos numéricos (TSChaveNFe)";
                }
            }

            if ($doc->tipoDocumento === 'NFSeMun') {
                if (empty($doc->codigoMunicipioNFSe)) {
                    $errors[] = "{$pfx}: cMunNFSeMun é obrigatório";
                } elseif (!preg_match('/^[0-9]{7}$/', $doc->codigoMunicipioNFSe)) {
                    $errors[] = "{$pfx}: cMunNFSeMun deve ter 7 dígitos numéricos (TSCodMunIBGE)";
                }
                if (empty($doc->numeroNFSe)) {
                    $errors[] = "{$pfx}: nNFSeMun é obrigatório";
                } elseif (!preg_match('/^[0-9]{15}$/', $doc->numeroNFSe)) {
                    $errors[] = "{$pfx}: nNFSeMun deve ter 15 dígitos numéricos (TSNum15Dig)";
                }
                if (empty($doc->codigoVerificacaoNFSe)) {
                    $errors[] = "{$pfx}: cVerifNFSeMun é obrigatório";
                } elseif (!preg_match('/^[a-zA-Z0-9]{1,9}$/', $doc->codigoVerificacaoNFSe)) {
                    $errors[] = "{$pfx}: cVerifNFSeMun deve ser alfanumérico de 1 a 9 caracteres (TSCodVerificacao)";
                }
            }

            if ($doc->tipoDocumento === 'NFNFS') {
                if (empty($doc->numeroNFS)) {
                    $errors[] = "{$pfx}: nNFS é obrigatório";
                } elseif (!preg_match('/^[0-9]{7}$/', $doc->numeroNFS)) {
                    $errors[] = "{$pfx}: nNFS deve ter 7 dígitos numéricos (TSNum7Dig)";
                }
                if (empty($doc->modeloNFS)) {
                    $errors[] = "{$pfx}: modNFS é obrigatório";
                } elseif (!preg_match('/^[0-9]{15}$/', $doc->modeloNFS)) {
                    $errors[] = "{$pfx}: modNFS deve ter 15 dígitos numéricos (TSNum15Dig)";
                }
                if (empty($doc->serieNFS)) {
                    $errors[] = "{$pfx}: serieNFS é obrigatório";
                } elseif (!preg_match('/^[a-zA-Z0-9]{1,15}$/', $doc->serieNFS)) {
                    $errors[] = "{$pfx}: serieNFS deve ser alfanumérico de 1 a 15 caracteres (TSSerieNFNFS)";
                }
            }

            if ($doc->tipoDocumento === 'nDocFisc' && empty($doc->numeroDocFiscal)) {
                $errors[] = "{$pfx}: nDocFisc é obrigatório";
            }

            if ($doc->tipoDocumento === 'nDoc' && empty($doc->numeroDoc)) {
                $errors[] = "{$pfx}: nDoc é obrigatório";
            }

            if ($doc->dataEmissaoDoc === null || $doc->dataEmissaoDoc === '') {
                $errors[] = "{$pfx}: dtEmiDoc é obrigatório (xs:date)";
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dataEmissaoDoc)) {
                $errors[] = "{$pfx}: dtEmiDoc deve estar no formato AAAA-MM-DD (xs:date)";
            }

            if ($doc->tipoDeducaoReducao === null || $doc->tipoDeducaoReducao === '') {
                $errors[] = "{$pfx}: tpDedRed é obrigatório (TSIdeDedRed)";
            } elseif (!in_array($doc->tipoDeducaoReducao, ['1','2','3','4','5','6','7','8','9','99'], true)) {
                $errors[] = "{$pfx}: tpDedRed inválido (TSIdeDedRed)";
            }

            if ($doc->tipoDeducaoReducao === '99' && empty($doc->descricaoOutrasDeducoes)) {
                $errors[] = "{$pfx}: xDescOutDed é obrigatório quando tpDedRed=99";
            }

            if ($doc->descricaoOutrasDeducoes !== null && (strlen($doc->descricaoOutrasDeducoes) < 1 || strlen($doc->descricaoOutrasDeducoes) > 150)) {
                $errors[] = "{$pfx}: xDescOutDed deve ter 1 a 150 caracteres (TSDescOutDedRed)";
            }

            if ($doc->valorDedutivel === null || $doc->valorDedutivel === '') {
                $errors[] = "{$pfx}: vDedutivelRedutivel é obrigatório (TSDec15V2)";
            } elseif (!self::isDec15V2($doc->valorDedutivel)) {
                $errors[] = "{$pfx}: vDedutivelRedutivel deve seguir o padrão decimal de 2 casas (TSDec15V2)";
            }

            if ($doc->valorDeducao === null || $doc->valorDeducao === '') {
                $errors[] = "{$pfx}: vDeducaoReducao é obrigatório (TSDec15V2)";
            } elseif (!self::isDec15V2($doc->valorDeducao)) {
                $errors[] = "{$pfx}: vDeducaoReducao deve seguir o padrão decimal de 2 casas (TSDec15V2)";
            }

            if (self::isDec15V2((string) $doc->valorDedutivel) && self::isDec15V2((string) $doc->valorDeducao)
                && (float) $doc->valorDeducao > (float) $doc->valorDedutivel) {
                $errors[] = "{$pfx}: vDeducaoReducao deve ser menor ou igual a vDedutivelRedutivel";
            }
        }
    }

    /**
     * Valida o padrão oficial TSDec15V2: 1 a 15 dígitos inteiros mais 2 casas decimais
     * exatas (ou inteiro sem decimais). Pattern idêntico ao do XSD.
     */
    private static function isDec15V2(string $value): bool
    {
        return preg_match('/^(0|0\.[0-9]{2}|[1-9][0-9]{0,14}(\.[0-9]{2})?)$/', $value) === 1;
    }

    /** @param array<string> $errors */
    private function validateTribMun(ServicoRequest $s, array &$errors): void
    {
        if ($s->tribISSQN === null) {
            $errors[] = 'tribISSQN é obrigatório (TSTribISSQN)';
        } elseif (!in_array($s->tribISSQN, TributacaoIssqn::valores(), true)) {
            $errors[] = 'tribISSQN inválido (TSTribISSQN)';
        }

        if ($s->codigoPaisResultado !== null && !preg_match('/^[A-Z]{2}$/', $s->codigoPaisResultado)) {
            $errors[] = 'cPaisResult deve ser 2 letras maiúsculas (TSCodPaisISO)';
        }

        if ($s->tipoImunidade !== null && !in_array($s->tipoImunidade, [0,1,2,3,4,5], true)) {
            $errors[] = 'tpImunidade inválido (TSTipoImunidadeISSQN)';
        }

        if ($s->exigSusp !== null) {
            $es = $s->exigSusp;
            if ($es->tipoSuspensao === null) {
                $errors[] = 'tpSusp é obrigatório (TSOpExigSuspensa)';
            } elseif (!in_array($es->tipoSuspensao, [1,2], true)) {
                $errors[] = 'tpSusp inválido (TSOpExigSuspensa)';
            }
            if ($es->numeroProcesso === null || $es->numeroProcesso === '') {
                $errors[] = 'nProcesso é obrigatório (TSNumProcExigSuspensa)';
            } elseif (!preg_match('/^[0-9]{30}$/', $es->numeroProcesso)) {
                $errors[] = 'nProcesso deve ter 30 dígitos numéricos (TSNumProcExigSuspensa)';
            }
        }

        if ($s->beneficioMunicipal !== null) {
            $bm = $s->beneficioMunicipal;
            if (empty($bm->numeroBeneficio)) {
                $errors[] = 'nBM é obrigatório (TSNumBeneficioMunicipal)';
            } elseif (!preg_match('/^[0-9]{14}$/', $bm->numeroBeneficio)) {
                $errors[] = 'nBM deve ter 14 dígitos numéricos (TSNumBeneficioMunicipal)';
            }

            $hasVRed = $bm->valorReducaoBC !== null;
            $hasPRed = $bm->percentualReducaoBC !== null;

            if ($hasVRed && $hasPRed) {
                $errors[] = 'vRedBCBM e pRedBCBM são mutuamente exclusivos (TCBM choice)';
            }

            if ($hasVRed && $bm->valorReducaoBC <= 0) {
                $errors[] = 'vRedBCBM deve ser maior que zero (TSDec15V2)';
            }

            if ($hasPRed && ($bm->percentualReducaoBC < 0 || $bm->percentualReducaoBC > 100)) {
                $errors[] = 'pRedBCBM deve estar entre 0 e 100 (TSDec3V2)';
            }

            if ($hasVRed && $bm->valorReducaoBC > $s->valorServicos) {
                $errors[] = 'vRedBCBM não pode exceder vServ (base de cálculo negativa)';
            }
        }

        if ($s->tpRetISSQN === null) {
            $errors[] = 'tpRetISSQN é obrigatório (TSTipoRetISSQN)';
        } elseif (!in_array($s->tpRetISSQN, TipoRetencaoIssqn::valores(), true)) {
            $errors[] = 'tpRetISSQN inválido (TSTipoRetISSQN)';
        }
    }

    /** @param array<string> $errors */
    private function validateTribFed(ServicoRequest $s, array &$errors): void
    {
        $tf = $s->tribFederal;
        if ($tf === null) {
            return;
        }

        if ($tf->pisCofinsCst !== null && !in_array($tf->pisCofinsCst, self::CST_PISCOFINS, true)) {
            $errors[] = 'CST do PIS/COFINS inválido — deve ser um dos códigos do domínio oficial (TSTipoCST)';
        }

        // pAliqPis/pAliqCofins são TSDec2V2: até 2 dígitos inteiros + 2 decimais → máximo 99.99.
        if ($tf->pisCofinsAliquotaPis !== null && ($tf->pisCofinsAliquotaPis < 0 || $tf->pisCofinsAliquotaPis > 99.99)) {
            $errors[] = 'pAliqPis deve estar entre 0 e 99.99 (TSDec2V2)';
        }

        if ($tf->pisCofinsAliquotaCofins !== null && ($tf->pisCofinsAliquotaCofins < 0 || $tf->pisCofinsAliquotaCofins > 99.99)) {
            $errors[] = 'pAliqCofins deve estar entre 0 e 99.99 (TSDec2V2)';
        }

        // CST é minOccurs=1 no grupo piscofins (TCTribOutrosPisCofins). O builder emite o grupo quando
        // CST OU pAliqPis está presente — espelhamos essa condição para exigir CST sempre que o grupo sair.
        if ($tf->pisCofinsAliquotaPis !== null && $tf->pisCofinsCst === null) {
            $errors[] = 'CST do PIS/COFINS é obrigatório quando pAliqPis é informado (TCTribOutrosPisCofins)';
        }

        if ($tf->pisCofinsCst !== null && $tf->pisCofinsTipo === null) {
            $errors[] = 'tipo do PIS/COFINS é obrigatório quando CST é informado (TSTipoRetPISCofins)';
        }

        if ($tf->pisCofinsTipo !== null && !in_array($tf->pisCofinsTipo, ['0','1','2','3','4','5','6','7','8','9'], true)) {
            $errors[] = 'tipo do PIS/COFINS inválido (TSTipoRetPISCofins)';
        }
    }

    /** @param array<string> $errors */
    private function validateTotTrib(ServicoRequest $s, array &$errors): void
    {
        $validTypes = ['vTotTrib', 'pTotTrib', 'indTotTrib', 'pTotTribSN'];
        if ($s->totTribTipo === null || !in_array($s->totTribTipo, $validTypes, true)) {
            $errors[] = 'totTribTipo é obrigatório — deve ser vTotTrib, pTotTrib, indTotTrib ou pTotTribSN (TCTribTotal choice)';

            return;
        }

        if ($s->totTribTipo === 'vTotTrib') {
            if ($s->vTotTribFed === null) {
                $errors[] = 'vTotTribFed é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            if ($s->vTotTribEst === null) {
                $errors[] = 'vTotTribEst é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            if ($s->vTotTribMun === null) {
                $errors[] = 'vTotTribMun é obrigatório quando totTribTipo=vTotTrib (TSDec15V2)';
            }
            foreach (['vTotTribFed' => $s->vTotTribFed, 'vTotTribEst' => $s->vTotTribEst, 'vTotTribMun' => $s->vTotTribMun] as $campo => $valor) {
                if ($valor !== null && $valor < 0) {
                    $errors[] = "{$campo} não pode ser negativo (TSDec15V2)";
                }
            }
        }

        if ($s->totTribTipo === 'pTotTrib') {
            if ($s->pTotTribFed === null) {
                $errors[] = 'pTotTribFed é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            if ($s->pTotTribEst === null) {
                $errors[] = 'pTotTribEst é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            if ($s->pTotTribMun === null) {
                $errors[] = 'pTotTribMun é obrigatório quando totTribTipo=pTotTrib (TSDec3V2)';
            }
            // pTotTribFed/Est/Mun são TSDec3V2 → máximo 999.99.
            foreach (['pTotTribFed' => $s->pTotTribFed, 'pTotTribEst' => $s->pTotTribEst, 'pTotTribMun' => $s->pTotTribMun] as $campo => $valor) {
                if ($valor !== null && ($valor < 0 || $valor > 999.99)) {
                    $errors[] = "{$campo} deve estar entre 0 e 999.99 (TSDec3V2)";
                }
            }
        }

        if ($s->totTribTipo === 'indTotTrib' && $s->indTotTrib === null) {
            $errors[] = 'indTotTrib é obrigatório quando totTribTipo=indTotTrib (TSTipoIndTotTrib)';
        }

        if ($s->totTribTipo === 'pTotTribSN') {
            if ($s->pTotTribSN === null) {
                $errors[] = 'pTotTribSN é obrigatório quando totTribTipo=pTotTribSN (TSDec2V2)';
            } elseif ($s->pTotTribSN < 0 || $s->pTotTribSN > 99.99) {
                // pTotTribSN é TSDec2V2 → máximo 99.99.
                $errors[] = 'pTotTribSN deve estar entre 0 e 99.99 (TSDec2V2)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbs(DpsRequest $request, array &$errors): void
    {
        $req = $request->ibscbs;
        if ($req === null) {
            return;
        }

        $dataCompetencia = new \DateTimeImmutable($request->dataCompetencia);
        $dataLimite = new \DateTimeImmutable('2026-01-01');
        if ($dataCompetencia < $dataLimite) {
            $errors[] = 'E0850: IBS/CBS permitido somente a partir de 01/01/2026';
        }

        if (empty($request->servico->codigoNbs)) {
            $errors[] = 'E1508: Código NBS é obrigatório quando IBS/CBS é declarado';
        }

        if ($req->finNFSe === '') {
            $errors[] = 'finNFSe é obrigatória (TSRTCFinNFSe)';
        } elseif (!in_array($req->finNFSe, FinalidadeNfse::valores(), true)) {
            $errors[] = 'finNFSe inválido (TSRTCFinNFSe)';
        }

        if ($req->cIndOp === '') {
            $errors[] = 'cIndOp é obrigatório (TSRTCCodIndOp)';
        } elseif (!preg_match('/^[0-9]{6}$/', $req->cIndOp)) {
            $errors[] = 'cIndOp deve ter 6 dígitos numéricos (TSRTCCodIndOp)';
        }

        if ($req->indDest === '') {
            $errors[] = 'indDest é obrigatório (TSRTCIndDest)';
        } elseif (!in_array($req->indDest, IndicadorDestinacao::valores(), true)) {
            $errors[] = 'indDest inválido (TSRTCIndDest)';
        }

        if ($req->cst === '') {
            $errors[] = 'CST é obrigatório (TSRTCCodSitTrib)';
        } elseif (!preg_match('/^[0-9]{3}$/', $req->cst)) {
            $errors[] = 'CST deve ter 3 dígitos numéricos (TSRTCCodSitTrib)';
        }

        if ($req->cClassTrib === '') {
            $errors[] = 'cClassTrib é obrigatório (TSRTCCodClassTrib)';
        } elseif (!preg_match('/^[0-9]{6}$/', $req->cClassTrib)) {
            $errors[] = 'cClassTrib deve ter 6 dígitos numéricos (TSRTCCodClassTrib)';
        }

        if ($req->cst !== '' && $req->cClassTrib !== '' && substr($req->cClassTrib, 0, 3) !== $req->cst) {
            $errors[] = 'E0959: 3 primeiros dígitos de cClassTrib devem ser iguais ao CST';
        }

        if ($req->indFinal !== null && $req->indFinal !== '' && !in_array($req->indFinal, IndicadorFinal::valores(), true)) {
            $errors[] = 'indFinal inválido (TSRTCIndFinal)';
        }

        if ($req->tpOper !== null && $req->tpOper !== '' && !in_array($req->tpOper, TipoOperacao::valores(), true)) {
            $errors[] = 'tpOper inválido (TSRTCTpOper)';
        }

        if ($req->tpEnteGov !== null && $req->tpEnteGov !== '' && !in_array($req->tpEnteGov, TipoEnteGovernamental::valores(), true)) {
            $errors[] = 'tpEnteGov inválido (TSRTCTpEnteGov)';
        }

        if ($req->cCredPres !== null && !preg_match('/^[0-9]{2}$/', $req->cCredPres)) {
            $errors[] = 'cCredPres deve ter 2 dígitos numéricos (TSRTCCodCredPres)';
        }

        if ($req->tribRegular !== null) {
            if (substr($req->tribRegular->cClassTribReg, 0, 3) !== $req->tribRegular->cstReg) {
                $errors[] = 'E0970: 3 primeiros dígitos de cClassTribReg devem ser iguais ao CSTReg';
            }
        }

        if ($req->indDest === '0' && $req->dest !== null) {
            $errors[] = 'E0910: destinatário não deve ser informado quando indDest=0';
        }
        if ($req->indDest === '1' && $req->dest === null) {
            $errors[] = 'E0910: destinatário deve ser informado quando indDest=1';
        }

        $this->validateIbsCbsDest($req, $errors);
        $this->validateIbsCbsImovel($req, $errors);
        $this->validateIbsCbsRefNFSe($req, $errors);
        $this->validateIbsCbsReeRepRes($req, $errors);
        $this->validateIbsCbsDiferimento($req, $errors);
        $this->validateIbsCbsTribRegular($req, $errors);
        $this->validateIbsCbsCstClassTrib($req, $errors);
    }

    /** @param array<string> $errors */
    private function validateIbsCbsDest(IbsCbsRequest $req, array &$errors): void
    {
        $dest = $req->dest;
        if ($dest === null) {
            return;
        }

        if (empty($dest->xNome)) {
            $errors[] = 'xNome do destinatário é obrigatório (TSDesc150)';
        } elseif (strlen($dest->xNome) > 150) {
            $errors[] = 'xNome do destinatário deve ter no máximo 150 caracteres (TSDesc150)';
        }

        $idCount = 0;
        if ($dest->cnpj !== null) {
            $idCount++;
        }
        if ($dest->cpf !== null) {
            $idCount++;
        }
        if ($dest->nif !== null) {
            $idCount++;
        }
        if ($dest->codigoNaoNif !== null) {
            $idCount++;
        }

        if ($idCount === 0) {
            $errors[] = 'Destinatário deve ter CNPJ, CPF, NIF ou cNaoNIF (TCRTCInfoDest choice)';
        }
        if ($idCount > 1) {
            $errors[] = 'Destinatário: CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos';
        }

        if ($dest->cnpj !== null && !preg_match('/^[0-9]{14}$/', $dest->cnpj)) {
            $errors[] = 'CNPJ do destinatário deve ter 14 dígitos numéricos (TSCNPJ)';
        }
        if ($dest->cpf !== null && !preg_match('/^[0-9]{11}$/', $dest->cpf)) {
            $errors[] = 'CPF do destinatário deve ter 11 dígitos numéricos (TSCPF)';
        }
        if ($dest->nif !== null && (strlen($dest->nif) < 1 || strlen($dest->nif) > 40)) {
            $errors[] = 'NIF do destinatário deve ter 1 a 40 caracteres (TSNIF)';
        }
        if ($dest->codigoNaoNif !== null && !in_array($dest->codigoNaoNif, CausaNaoNif::valores(), true)) {
            $errors[] = 'cNaoNIF do destinatário inválido (TSCodNaoNIF)';
        }

        $destEhExterior = $dest->codigoPais !== null
            || $dest->codigoPostalExterior !== null
            || $dest->nomeCidadeExterior !== null
            || $dest->estadoProvinciaExterior !== null;

        $temEnderecoNac = $dest->codigoMunicipio !== null
            || $dest->uf !== null
            || $dest->cep !== null;

        $temEnderecoDest = $dest->logradouro !== null
            || $dest->numero !== null
            || $dest->complemento !== null
            || $dest->bairro !== null
            || $temEnderecoNac
            || $destEhExterior;

        if ($temEnderecoDest) {
            // Comuns ao TCEndereco (válidos para endNac e endExt).
            if ($dest->logradouro === null || strlen($dest->logradouro) < 1 || strlen($dest->logradouro) > 255) {
                $errors[] = 'xLgr do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 255 caracteres (TSLogradouro)';
            }
            if ($dest->numero === null || strlen($dest->numero) < 1 || strlen($dest->numero) > 60) {
                $errors[] = 'nro do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 60 caracteres (TSNumeroEndereco)';
            }
            if ($dest->complemento !== null && (strlen($dest->complemento) < 1 || strlen($dest->complemento) > 156)) {
                $errors[] = 'xCpl do destinatário deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if ($dest->bairro === null || strlen($dest->bairro) < 1 || strlen($dest->bairro) > 60) {
                $errors[] = 'xBairro do destinatário é obrigatório quando o endereço é informado e deve ter 1 a 60 caracteres (TSBairro)';
            }

            if ($destEhExterior) {
                if ($temEnderecoNac) {
                    $errors[] = 'Destinatário: endereço nacional (cMun/CEP/UF) e exterior são mutuamente exclusivos (TCEndereco choice)';
                }
                if ($dest->codigoPais === null || !preg_match('/^[A-Z]{2}$/', $dest->codigoPais)) {
                    $errors[] = 'cPais do destinatário é obrigatório no endExt e deve ter 2 letras maiúsculas (TSCodPaisISO)';
                }
                if ($dest->codigoPostalExterior === null || strlen($dest->codigoPostalExterior) < 1 || strlen($dest->codigoPostalExterior) > 11) {
                    $errors[] = 'cEndPost do destinatário é obrigatório no endExt e deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if ($dest->nomeCidadeExterior === null || strlen($dest->nomeCidadeExterior) < 1 || strlen($dest->nomeCidadeExterior) > 60) {
                    $errors[] = 'xCidade do destinatário é obrigatório no endExt e deve ter 1 a 60 caracteres (TSCidade)';
                }
                if ($dest->estadoProvinciaExterior === null || strlen($dest->estadoProvinciaExterior) < 1 || strlen($dest->estadoProvinciaExterior) > 60) {
                    $errors[] = 'xEstProvReg do destinatário é obrigatório no endExt e deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            } else {
                // Ramo endNac (TCEnderNac): cMun + CEP obrigatórios.
                if ($dest->codigoMunicipio === null || !preg_match('/^[0-9]{7}$/', $dest->codigoMunicipio)) {
                    $errors[] = 'cMun do destinatário é obrigatório no endNac e deve ter 7 dígitos numéricos (TSCodMunIBGE)';
                }
                if ($dest->cep === null || !preg_match('/^[0-9]{8}$/', $dest->cep)) {
                    $errors[] = 'CEP do destinatário é obrigatório no endNac e deve ter 8 dígitos numéricos (TSCEP)';
                }
                if ($dest->uf !== null && !in_array($dest->uf, self::UFS, true)) {
                    $errors[] = 'UF do destinatário inválida (TSUF)';
                }
            }
        }
        if ($dest->fone !== null && !preg_match('/^[0-9]{6,20}$/', $dest->fone)) {
            $errors[] = 'fone do destinatário deve ter 6 a 20 dígitos numéricos (TSTelefone)';
        }
        if ($dest->email !== null && (strlen($dest->email) < 1 || strlen($dest->email) > 80)) {
            $errors[] = 'email do destinatário deve ter 1 a 80 caracteres (TSEmail)';
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsImovel(IbsCbsRequest $req, array &$errors): void
    {
        $im = $req->imovel;
        if ($im === null) {
            return;
        }

        if ($im->inscImobFisc !== null && (strlen($im->inscImobFisc) < 1 || strlen($im->inscImobFisc) > 30)) {
            $errors[] = 'inscImobFisc deve ter 1 a 30 caracteres (TSInscImobFisc)';
        }

        if ($im->cCIB !== null && $im->endereco !== null) {
            $errors[] = 'cCIB e end são mutuamente exclusivos no grupo imovel (TCRTCInfoImovel choice)';
        }
        if ($im->cCIB === null && $im->endereco === null) {
            $errors[] = 'É obrigatório informar cCIB ou end no grupo imovel (TCRTCInfoImovel choice)';
        }

        if ($im->cCIB !== null && mb_strlen($im->cCIB) !== 8) {
            $errors[] = 'cCIB deve ter exatamente 8 caracteres (TSCodCIB)';
        }

        if ($im->endereco !== null) {
            $e = $im->endereco;

            if ($e->cep !== null && $e->endExt !== null) {
                $errors[] = 'CEP e endExt são mutuamente exclusivos no endereço do imóvel (choice)';
            }
            if ($e->cep === null && $e->endExt === null) {
                $errors[] = 'É obrigatório informar CEP ou endExt no endereço do imóvel (choice)';
            }

            if ($e->cep !== null && !preg_match('/^[0-9]{8}$/', $e->cep)) {
                $errors[] = 'CEP do endereço do imóvel deve ter 8 dígitos numéricos (TSCEP)';
            }

            if ($e->endExt !== null) {
                if (strlen($e->endExt->cEndPost) < 1 || strlen($e->endExt->cEndPost) > 11) {
                    $errors[] = 'cEndPost do endExt do imóvel deve ter 1 a 11 caracteres (TSCodigoEndPostal)';
                }
                if (strlen($e->endExt->xCidade) < 1 || strlen($e->endExt->xCidade) > 60) {
                    $errors[] = 'xCidade do endExt do imóvel deve ter 1 a 60 caracteres (TSCidade)';
                }
                if (strlen($e->endExt->xEstProvReg) < 1 || strlen($e->endExt->xEstProvReg) > 60) {
                    $errors[] = 'xEstProvReg do endExt do imóvel deve ter 1 a 60 caracteres (TSEstadoProvRegiao)';
                }
            }

            if (empty($e->xLgr)) {
                $errors[] = 'xLgr é obrigatório no endereço do imóvel (TSLogradouro)';
            } elseif (strlen($e->xLgr) > 255) {
                $errors[] = 'xLgr deve ter no máximo 255 caracteres (TSLogradouro)';
            }
            if (empty($e->nro)) {
                $errors[] = 'nro é obrigatório no endereço do imóvel (TSNumeroEndereco)';
            } elseif (strlen($e->nro) > 60) {
                $errors[] = 'nro deve ter no máximo 60 caracteres (TSNumeroEndereco)';
            }
            if ($e->xCpl !== null && (strlen($e->xCpl) < 1 || strlen($e->xCpl) > 156)) {
                $errors[] = 'xCpl deve ter 1 a 156 caracteres (TSComplementoEndereco)';
            }
            if (empty($e->xBairro)) {
                $errors[] = 'xBairro é obrigatório no endereço do imóvel (TSBairro)';
            } elseif (strlen($e->xBairro) > 60) {
                $errors[] = 'xBairro deve ter no máximo 60 caracteres (TSBairro)';
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsRefNFSe(IbsCbsRequest $req, array &$errors): void
    {
        if ($req->tpOper !== null && $req->tpOper !== '') {
            $tpOper = (int) $req->tpOper;
            $hasRef = $req->refNFSeList !== null && $req->refNFSeList !== [];

            if (($tpOper === 2 || $tpOper === 3) && !$hasRef) {
                $errors[] = 'gRefNFSe deve ser informado quando tpOper=2 ou 3';
            }
            if (!($tpOper === 2 || $tpOper === 3) && $hasRef) {
                $errors[] = 'gRefNFSe não pode ser informado para o tpOper informado';
            }
        } elseif ($req->refNFSeList !== null && $req->refNFSeList !== []) {
            $errors[] = 'gRefNFSe não pode ser informado se tpOper não foi informado';
        }

        if ($req->refNFSeList !== null) {
            if (count($req->refNFSeList) > 99) {
                $errors[] = 'gRefNFSe deve conter no máximo 99 refNFSe (maxOccurs=99)';
            }
            foreach ($req->refNFSeList as $i => $chave) {
                if (!preg_match('/^[0-9]{50}$/', $chave)) {
                    $errors[] = "E0907: refNFSe #{$i} deve ter 50 dígitos numéricos (TSChaveNFSe)";
                }
            }
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsReeRepRes(IbsCbsRequest $req, array &$errors): void
    {
        $ree = $req->reeRepRes;
        if ($ree === null) {
            return;
        }

        if (empty($ree->documentos)) {
            $errors[] = 'gReeRepRes deve conter ao menos um documento';
        }

        if (count($ree->documentos) > 1000) {
            $errors[] = 'gReeRepRes deve conter no máximo 1000 documentos (maxOccurs=1000)';
        }

        foreach ($ree->documentos as $i => $doc) {
            $pfx = "Documento #{$i}";

            $this->validateIbsCbsFornecedor($doc->fornec, $pfx, $errors);

            if (!in_array($doc->tipoDocumento, ['dFeNacional', 'docFiscalOutro', 'docOutro'], true)) {
                $errors[] = "{$pfx}: tipoDocumento inválido (TCRTCListaDoc choice)";
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dtEmiDoc)) {
                $errors[] = "{$pfx}: dtEmiDoc deve estar no formato AAAA-MM-DD (TSData)";
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $doc->dtCompDoc)) {
                $errors[] = "{$pfx}: dtCompDoc deve estar no formato AAAA-MM-DD (TSData)";
            }

            if (!in_array($doc->tpReeRepRes, TipoReembolsoRepasseRessarcimento::valores(), true)) {
                $errors[] = "{$pfx}: tpReeRepRes inválido (TSRTCTpReeRepRes)";
            }

            if ($doc->vlrReeRepRes <= 0) {
                $errors[] = "{$pfx}: vlrReeRepRes deve ser maior que zero (TSDec15V2)";
            }

            if ($doc->tpReeRepRes === '99' && empty($doc->xTpReeRepRes)) {
                $errors[] = "{$pfx}: xTpReeRepRes é obrigatório quando tpReeRepRes=99 (TSDesc150)";
            }

            if ($doc->xTpReeRepRes !== null && (strlen($doc->xTpReeRepRes) < 1 || strlen($doc->xTpReeRepRes) > 150)) {
                $errors[] = "{$pfx}: xTpReeRepRes deve ter 1 a 150 caracteres (TSDesc150)";
            }

            if ($doc->tipoDocumento === 'dFeNacional') {
                if (!in_array($doc->tipoChaveDFe, TipoChaveDocumentoFiscal::valores(), true)) {
                    $errors[] = "{$pfx}: tipoChaveDFe inválido (TSRTCTipoChaveDFe)";
                }
                if (empty($doc->chaveDFe)) {
                    $errors[] = "{$pfx}: chaveDFe é obrigatória para dFeNacional (TSRTCChaveDFe)";
                } elseif (strlen($doc->chaveDFe) > 50) {
                    $errors[] = "{$pfx}: chaveDFe deve ter no máximo 50 caracteres (TSRTCChaveDFe)";
                }
                if ($doc->tipoChaveDFe === '9' && empty($doc->xTipoChaveDFe)) {
                    $errors[] = "{$pfx}: xTipoChaveDFe é obrigatório quando tipoChaveDFe=9 (TSDesc255)";
                }
                if ($doc->xTipoChaveDFe !== null && (strlen($doc->xTipoChaveDFe) < 1 || strlen($doc->xTipoChaveDFe) > 255)) {
                    $errors[] = "{$pfx}: xTipoChaveDFe deve ter 1 a 255 caracteres (TSDesc255)";
                }
            }

            if ($doc->tipoDocumento === 'docFiscalOutro') {
                if (!preg_match('/^\d{7}$/', $doc->cMunDocFiscal ?? '')) {
                    $errors[] = "{$pfx}: cMunDocFiscal deve ter 7 dígitos numéricos (TSNum7Dig)";
                }
                if (empty($doc->nDocFiscal)) {
                    $errors[] = "{$pfx}: nDocFiscal é obrigatório para docFiscalOutro (TSDesc255)";
                } elseif (strlen($doc->nDocFiscal) > 255) {
                    $errors[] = "{$pfx}: nDocFiscal deve ter no máximo 255 caracteres (TSDesc255)";
                }
                if (empty($doc->xDocFiscal)) {
                    $errors[] = "{$pfx}: xDocFiscal é obrigatório para docFiscalOutro (TSDesc255)";
                } elseif (strlen($doc->xDocFiscal) > 255) {
                    $errors[] = "{$pfx}: xDocFiscal deve ter no máximo 255 caracteres (TSDesc255)";
                }
            }

            if ($doc->tipoDocumento === 'docOutro') {
                if (empty($doc->nDoc)) {
                    $errors[] = "{$pfx}: nDoc é obrigatório para docOutro (TSDesc255)";
                } elseif (strlen($doc->nDoc) > 255) {
                    $errors[] = "{$pfx}: nDoc deve ter no máximo 255 caracteres (TSDesc255)";
                }
                if (empty($doc->xDoc)) {
                    $errors[] = "{$pfx}: xDoc é obrigatório para docOutro (TSDesc255)";
                } elseif (strlen($doc->xDoc) > 255) {
                    $errors[] = "{$pfx}: xDoc deve ter no máximo 255 caracteres (TSDesc255)";
                }
            }
        }
    }

    /**
     * Valida o grupo fornec (TCRTCListaDocFornec) de um documento de reembolso/
     * repasse/ressarcimento. O grupo é opcional (0-1); quando informado, exige
     * exatamente um identificador (choice CNPJ|CPF|NIF|cNaoNIF) e xNome (1-1, 1-150).
     *
     * @param array<string> $errors
     */
    private function validateIbsCbsFornecedor(
        ?IbsCbsFornecedorRequest $fornec,
        string $pfx,
        array &$errors
    ): void {
        if ($fornec === null) {
            return;
        }

        $idCount = 0;
        if ($fornec->cnpj !== null) {
            $idCount++;
            if (!preg_match('/^[0-9]{14}$/', $fornec->cnpj)) {
                $errors[] = "{$pfx}: CNPJ do fornecedor deve ter 14 dígitos numéricos (TSCNPJ)";
            }
        }
        if ($fornec->cpf !== null) {
            $idCount++;
            if (!preg_match('/^[0-9]{11}$/', $fornec->cpf)) {
                $errors[] = "{$pfx}: CPF do fornecedor deve ter 11 dígitos numéricos (TSCPF)";
            }
        }
        if ($fornec->nif !== null) {
            $idCount++;
            if (strlen($fornec->nif) < 1 || strlen($fornec->nif) > 40) {
                $errors[] = "{$pfx}: NIF do fornecedor deve ter 1 a 40 caracteres (TSNIF)";
            }
        }
        if ($fornec->codigoNaoNif !== null) {
            $idCount++;
            if (!in_array($fornec->codigoNaoNif, CausaNaoNif::valores(), true)) {
                $errors[] = "{$pfx}: cNaoNIF do fornecedor inválido — deve ser 0, 1 ou 2 (TSCodNaoNIF)";
            }
        }

        if ($idCount === 0) {
            $errors[] = "{$pfx}: fornecedor deve ter CNPJ, CPF, NIF ou cNaoNIF (choice obrigatório)";
        }
        if ($idCount > 1) {
            $errors[] = "{$pfx}: fornecedor — CNPJ, CPF, NIF e cNaoNIF são mutuamente exclusivos";
        }

        if (strlen($fornec->xNome) < 1 || strlen($fornec->xNome) > 150) {
            $errors[] = "{$pfx}: xNome do fornecedor deve ter 1 a 150 caracteres (TSDesc150)";
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsDiferimento(IbsCbsRequest $req, array &$errors): void
    {
        $dif = $req->diferimento;
        if ($dif === null) {
            return;
        }

        if ($dif->pDifUF < 0 || $dif->pDifUF > 100) {
            $errors[] = 'pDifUF deve estar entre 0 e 100 (TSDec3V2)';
        }
        if ($dif->pDifMun < 0 || $dif->pDifMun > 100) {
            $errors[] = 'pDifMun deve estar entre 0 e 100 (TSDec3V2)';
        }
        if ($dif->pDifCBS < 0 || $dif->pDifCBS > 100) {
            $errors[] = 'pDifCBS deve estar entre 0 e 100 (TSDec3V2)';
        }

        if ($dif->pDifUF <= 0 && $dif->pDifMun <= 0 && $dif->pDifCBS <= 0) {
            $errors[] = 'gDif informado mas todos os percentuais (pDifUF/pDifMun/pDifCBS) são zero — omita o grupo de diferimento';
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsTribRegular(IbsCbsRequest $req, array &$errors): void
    {
        $tr = $req->tribRegular;
        if ($tr === null) {
            return;
        }

        if (!preg_match('/^[0-9]{3}$/', $tr->cstReg)) {
            $errors[] = 'CSTReg deve ter 3 dígitos numéricos (TSRTCCodSitTrib)';
        }
        if (!preg_match('/^[0-9]{6}$/', $tr->cClassTribReg)) {
            $errors[] = 'cClassTribReg deve ter 6 dígitos numéricos (TSRTCCodClassTrib)';
        }
    }

    /** @param array<string> $errors */
    private function validateIbsCbsCstClassTrib(IbsCbsRequest $req, array &$errors): void
    {
        if ($this->cstClassTribRepository === null || $req->cClassTrib === '') {
            return;
        }

        $props = $this->cstClassTribRepository->findByCode($req->cClassTrib);

        if ($props === null) {
            $errors[] = "cClassTrib '{$req->cClassTrib}' não encontrado na tabela oficial";
            return;
        }

        if (!$props->isValidoParaNfse()) {
            $errors[] = "cClassTrib '{$req->cClassTrib}' não é suportado para NFS-e";
        }

        if ($props->isPermiteDiferimento() && $req->diferimento === null) {
            $errors[] = 'gDif deve ser informado para o cClassTrib indicado';
        }
        if (!$props->isPermiteDiferimento() && $req->diferimento !== null) {
            $errors[] = 'gDif não deve ser informado para o cClassTrib indicado';
        }

        if ($props->isExigeGrupoTributacaoRegular() && $req->tribRegular === null) {
            $errors[] = 'gTribRegular deve ser informado para o cClassTrib indicado';
        }
        if (!$props->isExigeGrupoTributacaoRegular() && $req->tribRegular !== null) {
            $errors[] = 'gTribRegular não deve ser informado para o cClassTrib indicado';
        }

        if ($req->tribRegular !== null && $req->tribRegular->cClassTribReg !== '') {
            $propsReg = $this->cstClassTribRepository->findByCode($req->tribRegular->cClassTribReg);
            if ($propsReg === null) {
                $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não encontrado na tabela oficial";
            } elseif (!$propsReg->isValidoParaNfse()) {
                $errors[] = "cClassTribReg '{$req->tribRegular->cClassTribReg}' não é suportado para NFS-e";
            }
        }
    }

    /** @param array<string> $errors */
    private function validateSubstituicao(DpsRequest $request, array &$errors): void
    {
        $s = $request->substituicao;
        if ($s === null) {
            return;
        }

        if (!preg_match('/^[0-9]{50}$/', $s->chaveSubstituida)) {
            $errors[] = 'chSubstda deve ter 50 dígitos numéricos (TSChaveNFSe)';
        }

        if (!in_array($s->codigoMotivo, MotivoSubstituicao::valores(), true)) {
            $errors[] = 'cMotivo inválido (TSCodJustSubst)';
        }

        if ($s->codigoMotivo === '99') {
            if ($s->descricaoMotivo === null || trim($s->descricaoMotivo) === '') {
                $errors[] = 'xMotivo é obrigatório quando cMotivo=99 (TSMotivo)';
            }
        }

        if ($s->descricaoMotivo !== null) {
            $len = mb_strlen(trim($s->descricaoMotivo));
            if ($len > 0 && $len < 15) {
                $errors[] = 'xMotivo deve ter no mínimo 15 caracteres (TSMotivo)';
            }
            if ($len > 255) {
                $errors[] = 'xMotivo deve ter no máximo 255 caracteres (TSMotivo)';
            }
        }
    }
}
