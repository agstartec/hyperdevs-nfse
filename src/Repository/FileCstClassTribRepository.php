<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Repository;

use Hyperdevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperdevs\Nfse\Domain\ValueObject\CstClassTribProperties;

final class FileCstClassTribRepository implements CstClassTribRepository
{
    /** @var array<string, CstClassTribProperties>|null */
    private ?array $cache = null;

    public function __construct(
        private string $filePath,
    ) {
    }

    public function findByCode(string $cClassTrib): ?CstClassTribProperties
    {
        $this->load();

        return $this->cache[$cClassTrib] ?? null;
    }

    public function findByCst(string $cst): array
    {
        $this->load();

        return array_values(
            array_filter(
                $this->cache ?? [],
                fn (CstClassTribProperties $p) => $p->getCst() === $cst,
            )
        );
    }

    public function findValidosParaNfse(): array
    {
        $this->load();

        return array_values(
            array_filter(
                $this->cache ?? [],
                fn (CstClassTribProperties $p) => $p->isValidoParaNfse(),
            )
        );
    }

    private function load(): void
    {
        if ($this->cache !== null) {
            return;
        }

        if (!file_exists($this->filePath)) {
            $this->cache = [];

            return;
        }

        $json = file_get_contents($this->filePath);
        if ($json === false) {
            $this->cache = [];

            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->cache = [];

            return;
        }

        $this->cache = [];
        foreach ($data as $row) {
            $code = $row['cClassTrib'] ?? '';
            if ($code === '') {
                continue;
            }

            $this->cache[$code] = new CstClassTribProperties(
                cClassTrib: $code,
                cst: $row['cst'] ?? substr($code, 0, 3),
                descricao: $row['descricao'] ?? '',
                validoParaNfse: (bool) ($row['validoParaNfse'] ?? false),
                permiteDiferimento: (bool) ($row['permiteDiferimento'] ?? false),
                exigeGrupoTributacaoRegular: (bool) ($row['exigeGrupoTributacaoRegular'] ?? false),
                pRedIBS: isset($row['pRedIBS']) ? (float) $row['pRedIBS'] : null,
                pRedCBS: isset($row['pRedCBS']) ? (float) $row['pRedCBS'] : null,
            );
        }
    }
}
