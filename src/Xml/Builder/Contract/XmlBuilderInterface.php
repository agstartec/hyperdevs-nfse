<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Xml\Builder\Contract;

interface XmlBuilderInterface
{
    public function build(object $entity): string;
}
