<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Xml\Validator\Contract;

use Hyperdevs\Nfse\Domain\Enum\VersaoSchema;
use Hyperdevs\Nfse\Xml\Exception\XmlValidationException;

interface XsdValidatorInterface
{
    /** @throws XmlValidationException */
    public function validate(string $xml, string $tipo, VersaoSchema $versao = VersaoSchema::V1_01): void;
}
