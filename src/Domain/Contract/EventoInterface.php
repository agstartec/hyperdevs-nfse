<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Contract;

use Hyperevs\Nfse\Domain\Enum\TipoEvento;

interface EventoInterface
{
    public function getTipo(): TipoEvento;
    public function getChaveNfse(): string;
    public function getDataEvento(): \DateTimeImmutable;
}
