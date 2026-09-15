<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Xml\Validator\Contract;

use Hyperevs\Nfse\Domain\Enum\VersaoSchema;
use Hyperevs\Nfse\Xml\Exception\XmlValidationException;

interface XsdValidatorInterface
{
    /** @throws XmlValidationException */
    public function validate(string $xml, string $tipo, VersaoSchema $versao = VersaoSchema::V1_01): void;
}
