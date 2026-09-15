<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Contract;

use Hyperevs\Nfse\Domain\ValueObject\CstClassTribProperties;

interface CstClassTribRepository
{
    public function findByCode(string $cClassTrib): ?CstClassTribProperties;

    /**
     * @return array<int, CstClassTribProperties>
     */
    public function findByCst(string $cst): array;

    /**
     * Retorna apenas os códigos efetivamente emitíveis em NFS-e
     * (validoParaNfse = true). Use este método para listar opções
     * aos clientes; findByCst() devolve a tabela oficial completa,
     * inclusive códigos não-serviço.
     *
     * @return array<int, CstClassTribProperties>
     */
    public function findValidosParaNfse(): array;
}
