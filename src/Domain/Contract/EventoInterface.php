<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Contract;

use Hyperdevs\Nfse\Domain\Enum\TipoEvento;

interface EventoInterface
{
    public function getTipo(): TipoEvento;
    public function getChaveNfse(): string;
    public function getDataEvento(): \DateTimeImmutable;
}
