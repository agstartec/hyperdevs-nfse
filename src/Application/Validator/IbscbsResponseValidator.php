<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Application\Validator;

use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Domain\Contract\CstClassTribRepository;

final class IbscbsResponseValidator
{
    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(
        private ?CstClassTribRepository $cstClassTribRepository = null,
    ) {
    }

    /**
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    public function validate(array $ibsData, array $nfseIbscbs): void
    {
        $this->errors = [];

        $this->validatePRedutor($ibsData, $nfseIbscbs);
        $this->validatePRedAliq($ibsData, $nfseIbscbs);
        $this->validateVCredPres($ibsData, $nfseIbscbs);
        $this->validateVDiferimento($ibsData, $nfseIbscbs);
        $this->validateGTribCompraGov($ibsData, $nfseIbscbs);
        $this->validateVCalcReeRepRes($ibsData, $nfseIbscbs);

        if ($this->errors !== []) {
            throw new ValidationException(implode('; ', $this->errors));
        }
    }

    private function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * E1522: pRedutor não deve ser informado se tpEnteGov não foi informado
     * E1523: pRedutor deve ser informado se tpEnteGov foi informado
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validatePRedutor(array $ibsData, array $nfseIbscbs): void
    {
        $tpEnteGov = $ibsData['tpEnteGov'] ?? null;
        $pRedutor = $nfseIbscbs['pRedutor'] ?? null;

        $hasPRedutor = $pRedutor !== null && (float) $pRedutor > 0.0;

        if ($tpEnteGov === null || $tpEnteGov === '') {
            if ($hasPRedutor) {
                $this->addError('E1522: pRedutor não deve ser informado quando tpEnteGov não foi informado na DPS');
            }
        } elseif (!$hasPRedutor) {
            $this->addError('E1523: pRedutor deve ser informado quando tpEnteGov foi informado na DPS');
        }
    }

    /**
     * E1540: pRedAliqUF não deve ser informado se cClassTrib não possui redução
     * E1541: pRedAliqUF deve ser informado se cClassTrib possui redução
     * E1545: pRedAliqMun não deve ser informado se cClassTrib não possui redução
     * E1546: pRedAliqMun deve ser informado se cClassTrib possui redução
     * E1550: pRedAliqCBS não deve ser informado se cClassTrib não possui redução
     * E1551: pRedAliqCBS deve ser informado se cClassTrib possui redução
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validatePRedAliq(array $ibsData, array $nfseIbscbs): void
    {
        if ($this->cstClassTribRepository === null) {
            return;
        }

        $cClassTrib = $ibsData['cClassTrib'] ?? null;
        if ($cClassTrib === null || $cClassTrib === '') {
            return;
        }

        $props = $this->cstClassTribRepository->findByCode($cClassTrib);
        if ($props === null) {
            return;
        }

        $hasRedIBS = $props->hasReducaoIBS();
        $hasRedCBS = $props->hasReducaoCBS();

        $pRedAliqUF = $nfseIbscbs['valores']['uf']['pRedAliqUF'] ?? null;
        $pRedAliqMun = $nfseIbscbs['valores']['mun']['pRedAliqMun'] ?? null;
        $pRedAliqCBS = $nfseIbscbs['valores']['fed']['pRedAliqCBS'] ?? null;

        $hasPRedAliqUF = $pRedAliqUF !== null && (float) $pRedAliqUF > 0.0;
        $hasPRedAliqMun = $pRedAliqMun !== null && (float) $pRedAliqMun > 0.0;
        $hasPRedAliqCBS = $pRedAliqCBS !== null && (float) $pRedAliqCBS > 0.0;

        if (!$hasRedIBS && $hasPRedAliqUF) {
            $this->addError('E1540: pRedAliqUF não deve ser informado para o cClassTrib indicado (código não possui redução de alíquota IBS)');
        }
        if ($hasRedIBS && !$hasPRedAliqUF) {
            $this->addError('E1541: pRedAliqUF deve ser informado para o cClassTrib indicado (código possui redução de alíquota IBS)');
        }

        if (!$hasRedIBS && $hasPRedAliqMun) {
            $this->addError('E1545: pRedAliqMun não deve ser informado para o cClassTrib indicado (código não possui redução de alíquota IBS)');
        }
        if ($hasRedIBS && !$hasPRedAliqMun) {
            $this->addError('E1546: pRedAliqMun deve ser informado para o cClassTrib indicado (código possui redução de alíquota IBS)');
        }

        if (!$hasRedCBS && $hasPRedAliqCBS) {
            $this->addError('E1550: pRedAliqCBS não deve ser informado para o cClassTrib indicado (código não possui redução de alíquota CBS)');
        }
        if ($hasRedCBS && !$hasPRedAliqCBS) {
            $this->addError('E1551: pRedAliqCBS deve ser informado para o cClassTrib indicado (código possui redução de alíquota CBS)');
        }
    }

    /**
     * E1560/E1561: gIBSCredPres não/deve ser informado conforme cCredPres
     * E1575/E1576: gCBSCredPres não/deve ser informado conforme cCredPres
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validateVCredPres(array $ibsData, array $nfseIbscbs): void
    {
        $cCredPres = $ibsData['cCredPres'] ?? null;

        $gIbs = $nfseIbscbs['totCIBS']['gIBS'] ?? null;
        $gCbs = $nfseIbscbs['totCIBS']['gCBS'] ?? null;

        $hasIbsCredPres = $gIbs !== null && ($gIbs['gIBSCredPres'] ?? null) !== null;
        $hasCbsCredPres = $gCbs !== null && ($gCbs['gCBSCredPres'] ?? null) !== null;

        $hasCCredPres = $cCredPres !== null && $cCredPres !== '';

        if (!$hasCCredPres && $hasIbsCredPres) {
            $this->addError('E1560: gIBSCredPres não deve ser informado quando cCredPres não foi informado na DPS');
        }
        if ($hasCCredPres && !$hasIbsCredPres) {
            $this->addError('E1561: gIBSCredPres deve ser informado quando cCredPres foi informado na DPS');
        }
        if (!$hasCCredPres && $hasCbsCredPres) {
            $this->addError('E1575: gCBSCredPres não deve ser informado quando cCredPres não foi informado na DPS');
        }
        if ($hasCCredPres && !$hasCbsCredPres) {
            $this->addError('E1576: gCBSCredPres deve ser informado quando cCredPres foi informado na DPS');
        }
    }

    /**
     * E1565/E1566: vDifUF não/deve ser informado conforme pDifUF
     * E1569/E1570: vDifMun não/deve ser informado conforme pDifMun
     * E1579/E1580: vDifCBS não/deve ser informado conforme pDifCBS
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validateVDiferimento(array $ibsData, array $nfseIbscbs): void
    {
        $gIbs = $nfseIbscbs['totCIBS']['gIBS'] ?? null;
        $gCbs = $nfseIbscbs['totCIBS']['gCBS'] ?? null;

        $gIbsUfTot = $gIbs['gIBSUFTot'] ?? null;
        $gIbsMunTot = $gIbs['gIBSMunTot'] ?? null;

        $pDifUF = $ibsData['diferimento']['pDifUF'] ?? null;
        $pDifMun = $ibsData['diferimento']['pDifMun'] ?? null;
        $pDifCBS = $ibsData['diferimento']['pDifCBS'] ?? null;

        // O diferimento é por esfera: pDif=0 significa "não diferido" naquela esfera, e a SEFIN
        // legitimamente NÃO retorna o vDif correspondente (vDif = vTrib x pDif = 0; vDif é minOccurs=0).
        // Por isso "informado" = maior que zero, não apenas não-nulo (pDif* é float não-nulável no grupo).
        $hasPDifUF = $pDifUF !== null && $pDifUF > 0;
        $hasPDifMun = $pDifMun !== null && $pDifMun > 0;
        $hasPDifCBS = $pDifCBS !== null && $pDifCBS > 0;

        $vDifUF = $gIbsUfTot !== null ? ($gIbsUfTot['vDifUF'] ?? null) : null;
        $vDifMun = $gIbsMunTot !== null ? ($gIbsMunTot['vDifMun'] ?? null) : null;
        $vDifCBS = $gCbs !== null ? ($gCbs['vDifCBS'] ?? null) : null;

        $hasVDifUF = $vDifUF !== null && (float) $vDifUF > 0.0;
        $hasVDifMun = $vDifMun !== null && (float) $vDifMun > 0.0;
        $hasVDifCBS = $vDifCBS !== null && (float) $vDifCBS > 0.0;

        if (!$hasPDifUF && $hasVDifUF) {
            $this->addError('E1565: vDifUF não deve ser informado quando pDifUF não foi informado na DPS');
        }
        if ($hasPDifUF && !$hasVDifUF) {
            $this->addError('E1566: vDifUF deve ser informado quando pDifUF foi informado na DPS');
        }
        if (!$hasPDifMun && $hasVDifMun) {
            $this->addError('E1569: vDifMun não deve ser informado quando pDifMun não foi informado na DPS');
        }
        if ($hasPDifMun && !$hasVDifMun) {
            $this->addError('E1570: vDifMun deve ser informado quando pDifMun foi informado na DPS');
        }
        if (!$hasPDifCBS && $hasVDifCBS) {
            $this->addError('E1579: vDifCBS não deve ser informado quando pDifCBS não foi informado na DPS');
        }
        if ($hasPDifCBS && !$hasVDifCBS) {
            $this->addError('E1580: vDifCBS deve ser informado quando pDifCBS foi informado na DPS');
        }
    }

    /**
     * E1600: gTribCompraGov não deve ser informado quando tpEnteGov não foi informado na DPS
     * E1601: gTribCompraGov deve ser informado quando tpEnteGov foi informado na DPS
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validateGTribCompraGov(array $ibsData, array $nfseIbscbs): void
    {
        $tpEnteGov = $ibsData['tpEnteGov'] ?? null;
        $gTribCompraGov = $nfseIbscbs['totCIBS']['gTribCompraGov'] ?? null;

        $hasTpEnteGov = $tpEnteGov !== null && $tpEnteGov !== '';
        $hasGTribCompraGov = $gTribCompraGov !== null;

        if (!$hasTpEnteGov && $hasGTribCompraGov) {
            $this->addError('E1600: gTribCompraGov não deve ser informado quando tpEnteGov não foi informado na DPS');
        }
        if ($hasTpEnteGov && !$hasGTribCompraGov) {
            $this->addError('E1601: gTribCompraGov deve ser informado quando tpEnteGov foi informado na DPS');
        }
    }

    /**
     * E1531: vCalcReeRepRes não deve ser informado se gRefNFSe não foi informado na DPS
     * E1533: vCalcReeRepRes deve ser informado se gRefNFSe foi informado na DPS
     * E1534: vCalcReeRepRes < vServ (quando informado)
     *
     * @param array<string, mixed> $ibsData
     * @param array<string, mixed> $nfseIbscbs
     */
    private function validateVCalcReeRepRes(array $ibsData, array $nfseIbscbs): void
    {
        $vCalcReeRepRes = $nfseIbscbs['valores']['vCalcReeRepRes'] ?? null;
        $hasVCalc = $vCalcReeRepRes !== null && (float) $vCalcReeRepRes > 0.0;

        $hasRefNFSe = isset($ibsData['refNFSeList'])
            && is_array($ibsData['refNFSeList'])
            && $ibsData['refNFSeList'] !== [];

        if (!$hasRefNFSe && $hasVCalc) {
            $this->addError('E1531: vCalcReeRepRes não deve ser informado quando gRefNFSe não foi informado na DPS');
        }
        if ($hasRefNFSe && !$hasVCalc) {
            $this->addError('E1533: vCalcReeRepRes deve ser informado quando gRefNFSe foi informado na DPS');
        }

        if (!$hasVCalc) {
            return;
        }

        $vServ = $ibsData['vServ'] ?? null;
        if ($vServ !== null) {
            $vCalcFloat = (float) $vCalcReeRepRes;
            $vServFloat = (float) $vServ;

            if ($vCalcFloat >= $vServFloat) {
                $this->addError('E1534: vCalcReeRepRes deve ser menor que o valor do serviço prestado');
            }
        }
    }
}
