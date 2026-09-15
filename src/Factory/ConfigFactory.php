<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Factory;

use Hyperdevs\Nfse\Domain\Enum\TipoAmbiente;
use Hyperdevs\Nfse\Config\Config;

class ConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): Config
    {
        return new Config($config);
    }

    public static function createDefault(string $prefeitura, int $tpAmb = 2): Config
    {
        return new Config([
            'tpAmb' => $tpAmb,
            'prefeitura' => $prefeitura,
        ]);
    }

    public static function createHomologacao(string $prefeitura): Config
    {
        return self::createDefault($prefeitura, TipoAmbiente::HOMOLOGACAO->value);
    }

    public static function createProducao(string $prefeitura): Config
    {
        return self::createDefault($prefeitura, TipoAmbiente::PRODUCAO->value);
    }
}
