<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Application\Service;

/**
 * Extrai a mensagem de erro real da resposta da SEFIN.
 *
 * A SEFIN (SefinNacional) retorna os erros em formato estruturado — `erros[]`
 * (emissão) ou `erro[]` (eventos) com `Codigo`+`Descricao` — e NÃO no campo
 * `mensagem`. A leitura ingênua de `data['mensagem']` caía sempre no fallback
 * genérico. O payload cru completo continua disponível em `dados` da resposta.
 */
trait SefinErrorMessageTrait
{
    /**
     * Extrai TODOS os erros estruturados da resposta da SEFIN, na ordem recebida.
     * A SEFIN pode retornar mais de um erro de uma vez (ex.: E0617 + E0625).
     *
     * @param mixed $data corpo já decodificado da resposta da API
     * @return list<array{codigo: string|null, descricao: string}> lista vazia se não houver erro estruturado
     */
    private function extrairErros(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $erros = $data['erros'] ?? $data['erro'] ?? null;
        if (!is_array($erros)) {
            return [];
        }

        $resultado = [];
        foreach ($erros as $erro) {
            if (is_array($erro)) {
                $codigo = $erro['codigo'] ?? $erro['Codigo'] ?? null;
                $descricao = $erro['descricao'] ?? $erro['Descricao']
                    ?? $erro['mensagem'] ?? $erro['Mensagem'] ?? null;
                if (is_string($descricao) && $descricao !== '') {
                    $resultado[] = [
                        'codigo' => is_string($codigo) && $codigo !== '' ? $codigo : null,
                        'descricao' => $descricao,
                    ];
                }
            } elseif (is_string($erro) && $erro !== '') {
                $resultado[] = ['codigo' => null, 'descricao' => $erro];
            }
        }

        return $resultado;
    }

    /**
     * Mensagem de erro legível: o primeiro erro estruturado (`"CODIGO - descrição"`)
     * ou, na ausência dele, um campo `mensagem`/`message` do topo, ou o fallback.
     * Para a lista completa, use extrairErros().
     *
     * @param mixed $data corpo já decodificado da resposta da API
     */
    private function extrairMensagemErro(mixed $data, string $fallback): string
    {
        $erros = $this->extrairErros($data);
        if ($erros !== []) {
            $primeiro = $erros[0];
            return $primeiro['codigo'] !== null
                ? "{$primeiro['codigo']} - {$primeiro['descricao']}"
                : $primeiro['descricao'];
        }

        if (is_array($data)) {
            foreach (['mensagem', 'message', 'Mensagem', 'Message'] as $chave) {
                if (isset($data[$chave]) && is_string($data[$chave]) && $data[$chave] !== '') {
                    return $data[$chave];
                }
            }
        }

        return $fallback;
    }
}
