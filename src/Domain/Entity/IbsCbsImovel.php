<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\ValueObject\CodigoCIB;

class IbsCbsImovel
{
    private ?CodigoCIB $cCIB;
    private ?IbsCbsEnderecoObra $endereco;

    public function __construct(
        private ?string $inscImobFisc = null,
        ?CodigoCIB $cCIB = null,
        ?IbsCbsEnderecoObra $endereco = null,
    ) {
        $preenchidos = 0;
        if ($cCIB !== null) {
            $preenchidos++;
        }
        if ($endereco !== null) {
            $preenchidos++;
        }

        if ($preenchidos !== 1) {
            throw new \InvalidArgumentException(
                'Imóvel deve informar exatamente um dos campos: cCIB ou endereco'
            );
        }

        $this->cCIB = $cCIB;
        $this->endereco = $endereco;
    }

    public function getInscImobFisc(): ?string
    {
        return $this->inscImobFisc;
    }

    public function getCCIB(): ?CodigoCIB
    {
        return $this->cCIB;
    }

    public function getEndereco(): ?IbsCbsEnderecoObra
    {
        return $this->endereco;
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
