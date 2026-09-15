<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\Validator;

use Hyperevs\Nfse\Application\DTO\Request\ConsultaRequest;
use Hyperevs\Nfse\Application\Exception\ValidationException;

class ConsultaValidator
{
    public function validate(ConsultaRequest $request): void
    {
        if (empty($request->chave)) {
            throw new ValidationException('Chave de acesso é obrigatória');
        }

        if (!preg_match('/^[0-9]{50}$/', $request->chave)) {
            throw new ValidationException('Chave de acesso deve ter exatamente 50 dígitos numéricos');
        }
    }
}
