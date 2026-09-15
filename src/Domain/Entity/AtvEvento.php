<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Entity;

class AtvEvento
{
    private ?string $identificacaoEvento;
    private ?IbsCbsEnderecoObra $endereco;

    public function __construct(
        private string $descricao,
        private \DateTimeImmutable $dataInicio,
        private \DateTimeImmutable $dataFim,
        ?string $identificacaoEvento = null,
        ?IbsCbsEnderecoObra $endereco = null,
    ) {
        $preenchidos = 0;
        if ($identificacaoEvento !== null) {
            $preenchidos++;
        }
        if ($endereco !== null) {
            $preenchidos++;
        }

        if ($preenchidos !== 1) {
            throw new \InvalidArgumentException(
                'Atividade/Evento deve informar exatamente um dos campos: identificacaoEvento ou endereco'
            );
        }

        $this->identificacaoEvento = $identificacaoEvento;
        $this->endereco = $endereco;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getDataInicio(): \DateTimeImmutable
    {
        return $this->dataInicio;
    }

    public function getDataFim(): \DateTimeImmutable
    {
        return $this->dataFim;
    }

    public function getIdentificacaoEvento(): ?string
    {
        return $this->identificacaoEvento;
    }

    public function getEndereco(): ?IbsCbsEnderecoObra
    {
        return $this->endereco;
    }

    public function isPorIdentificacao(): bool
    {
        return $this->identificacaoEvento !== null;
    }

    public function isPorEndereco(): bool
    {
        return $this->endereco !== null;
    }
}
