<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Security\Contract;

interface XmlSignerInterface
{
    public function sign(string $xml, string $tagname, string $rootname): string;
}
