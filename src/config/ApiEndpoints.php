<?php

namespace Hyperevs\Nfse\Config;

class ApiEndpoints
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function consultarNfse(string $chave): string
    {
        return str_replace('{chave}', $chave, $this->config->getOperation('consultar_nfse'));
    }

    public function consultarDps(string $chave): string
    {
        return str_replace('{chave}', $chave, $this->config->getOperation('consultar_dps'));
    }

    public function consultarEventos(string $chave, ?string $tipoEvento = null, ?int $nSequencial = null): string
    {
        $operation = str_replace('{chave}', $chave, $this->config->getOperation('consultar_eventos'));

        if ($tipoEvento === null) {
            $operation = str_replace('/{tipoEvento}/{nSequencial}', '', $operation);
        } else {
            $operation = str_replace('{tipoEvento}', $tipoEvento, $operation);
        }

        if ($nSequencial === null) {
            $operation = str_replace('/{nSequencial}', '', $operation);
        } else {
            $operation = str_replace('{nSequencial}', (string) $nSequencial, $operation);
        }

        return $operation;
    }

    public function emitirNfse(): string
    {
        return $this->config->getOperation('emitir_nfse');
    }

    public function cancelarNfse(string $chave): string
    {
        return str_replace('{chave}', $chave, $this->config->getOperation('cancelar_nfse'));
    }

    public function verificarDps(string $id): string
    {
        return str_replace('{id}', $id, $this->config->getOperation('verificar_dps'));
    }

    public function decisaoJudicialNfse(): string
    {
        return $this->config->getOperation('decisao_judicial_nfse');
    }
}