<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Security\Contract;

use Hyperevs\Nfse\Http\Security\Certificate;

interface CertificateManagerInterface
{
     public function getCertificate(): Certificate;
    /**
     * @return array{private: string, public: string, cert: string}
     */
    public function saveTemporaryFiles(): array;
}