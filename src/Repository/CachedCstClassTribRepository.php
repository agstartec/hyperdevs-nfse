<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Repository;

use Hyperdevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperdevs\Nfse\Domain\ValueObject\CstClassTribProperties;

final class CachedCstClassTribRepository implements CstClassTribRepository
{
    /** @var array<string, CstClassTribProperties|array<int, CstClassTribProperties>|null> */
    private array $cache = [];

    public function __construct(
        private CstClassTribRepository $inner,
    ) {
    }

    public function findByCode(string $cClassTrib): ?CstClassTribProperties
    {
        if (!array_key_exists($cClassTrib, $this->cache)) {
            $this->cache[$cClassTrib] = $this->inner->findByCode($cClassTrib);
        }

        $result = $this->cache[$cClassTrib];
        if ($result instanceof CstClassTribProperties || $result === null) {
            return $result;
        }

        return null;
    }

    public function findByCst(string $cst): array
    {
        $key = "__cst_{$cst}";
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->inner->findByCst($cst);
        }

        $result = $this->cache[$key];
        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    public function findValidosParaNfse(): array
    {
        $key = '__validos_nfse';
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->inner->findValidosParaNfse();
        }

        $result = $this->cache[$key];
        if (is_array($result)) {
            return $result;
        }

        return [];
    }
}
