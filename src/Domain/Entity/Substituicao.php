<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

use Hyperdevs\Nfse\Domain\ValueObject\ChaveAcesso;

class Substituicao
{
    public function __construct(
        private ChaveAcesso $chaveSubstituida,
        private string $codigoMotivo,
        private ?string $descricaoMotivo = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $codigosValidos = ['01', '02', '03', '04', '05', '99'];

        if (!in_array($this->codigoMotivo, $codigosValidos, true)) {
            throw new \InvalidArgumentException(
                "Código de motivo inválido: {$this->codigoMotivo}"
            );
        }

        if ($this->codigoMotivo === '99') {
            if ($this->descricaoMotivo === null || trim($this->descricaoMotivo) === '') {
                throw new \InvalidArgumentException(
                    'Descrição do motivo (xMotivo) é obrigatória quando cMotivo = 99'
                );
            }
        }

        if ($this->descricaoMotivo !== null) {
            $len = mb_strlen(trim($this->descricaoMotivo));

            if ($len < 15) {
                throw new \InvalidArgumentException(
                    'Descrição do motivo (xMotivo) deve ter no mínimo 15 caracteres'
                );
            }

            if ($len > 255) {
                throw new \InvalidArgumentException(
                    'Descrição do motivo (xMotivo) deve ter no máximo 255 caracteres'
                );
            }
        }
    }

    public function getChaveSubstituida(): ChaveAcesso
    {
        return $this->chaveSubstituida;
    }

    public function getCodigoMotivo(): string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): ?string
    {
        return $this->descricaoMotivo;
    }
}
