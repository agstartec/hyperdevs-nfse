<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Domain\Entity;

use Hyperevs\Nfse\Domain\ValueObject\CodigoCIB;

class Obra
{
    private ?string $cObra;
    private ?CodigoCIB $cCIB;
    private ?IbsCbsEnderecoObra $endereco;

    public function __construct(
        private ?string $inscImobFisc = null,
        ?string $cObra = null,
        ?CodigoCIB $cCIB = null,
        ?IbsCbsEnderecoObra $endereco = null,
    ) {
        $preenchidos = 0;
        if ($cObra !== null) {
            $preenchidos++;
        }
        if ($cCIB !== null) {
            $preenchidos++;
        }
        if ($endereco !== null) {
            $preenchidos++;
        }

        if ($preenchidos !== 1) {
            throw new \InvalidArgumentException(
                'Obra deve informar exatamente um dos campos: cObra (CNO/CEI), cCIB ou endereco'
            );
        }

        $this->cObra = $cObra;
        $this->cCIB = $cCIB;
        $this->endereco = $endereco;
    }

    public function getInscImobFisc(): ?string
    {
        return $this->inscImobFisc;
    }

    public function getCObra(): ?string
    {
        return $this->cObra;
    }

    public function getCCIB(): ?CodigoCIB
    {
        return $this->cCIB;
    }

    public function getEndereco(): ?IbsCbsEnderecoObra
    {
        return $this->endereco;
    }

    public function isPorCodigoObra(): bool
    {
        return $this->cObra !== null;
    }

    public function isPorCIB(): bool
    {
        return $this->cCIB !== null;
    }

    public function isPorEndereco(): bool
    {
        return $this->endereco !== null;
    }
}
