<?php
namespace Hyperevs\Nfse\Config;
use Hyperevs\Nfse\Config\Exception\ConfigException;
use Hyperevs\Nfse\Config\Contract\ConfigInterface;
use Hyperevs\Nfse\Domain\TipoAmbiente;
use Composer\InstalledVersions;

class Config implements ConfigInterface
{
    private const PACKAGE_NAME = 'hyperevs/nfse';

    /** @var array<string, mixed> */
    private array $config;
    /** @var array<string, string> */
    private array $urls;
    /** @var array<string, string> */
    private array $operations;
    private string $tipoApi;

    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->tipoApi = $config['tipoApi'] ?? 'sefin';

        $prefeituras = $this->loadPrefeituras();
        $this->loadUrls($prefeituras);
        $this->loadOperations($prefeituras);
    }

     private function validateConfig(array $config): array
    {
        $required = ['tpAmb', 'prefeitura'];

        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new ConfigException("Campo obrigatório não informado: {$field}");
            }
        }

        if (!in_array($config['tpAmb'], [1, 2, '1', '2'], true)) {
            throw new ConfigException('tpAmb deve ser 1 (Produção) ou 2 (Homologação)');
        }

        // Normaliza para int — TipoAmbiente é um enum int-backed e getTipoAmbiente() faz ::from().
        $config['tpAmb'] = (int) $config['tpAmb'];

        return $config;
    }

     private function loadPrefeituras(): array
    {
        $configFile = __DIR__ . '/../../Storage/prefeituras.json';
        if (!file_exists($configFile)) {
            return [];
        }

        $content = file_get_contents($configFile);
        if ($content === false || !json_validate($content)) {
            throw new ConfigException("Arquivo de configuração inválido: {$configFile}");
        }

        return json_decode($content, true);
    }

    private function loadUrls(array $prefeituras): void
    {
        $this->urls = [
            'sefin_homologacao' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional',
            'sefin_producao' => 'https://sefin.nfse.gov.br/sefinnacional',
            'adn_homologacao' => 'https://adn.producaorestrita.nfse.gov.br',
            'adn_producao' => 'https://adn.nfse.gov.br',
        ];

        $prefeitura = $this->config['prefeitura'];
        if (isset($prefeituras[$prefeitura]['urls'])) {
            $this->urls = array_merge($this->urls, $prefeituras[$prefeitura]['urls']);
        }
    }

    private function loadOperations(array $prefeituras): void
    {
        $this->operations = [
            'consultar_nfse' => 'nfse/{chave}',
            'consultar_dps' => 'dps/{chave}',
            'consultar_eventos' => 'nfse/{chave}/eventos/{tipoEvento}/{nSequencial}',
            'emitir_nfse' => 'nfse',
            'cancelar_nfse' => 'nfse/{chave}/eventos',
            'verificar_dps' => 'dps/{id}',
            'decisao_judicial_nfse' => 'decisao-judicial/nfse',
        ];

        $prefeitura = $this->config['prefeitura'];
        if (isset($prefeituras[$prefeitura]['operations'])) {
            $this->operations = array_merge($this->operations, $prefeituras[$prefeitura]['operations']);
        }
    }

    #[\Override]
    public function getTipoAmbiente(): TipoAmbiente
    {
        return TipoAmbiente::from($this->config['tpAmb']);
    }

    #[\Override]
    public function getTipoApi(): string
    {
        return $this->tipoApi;
    }

    #[\Override]
    public function getUrl(string $key): string
    {
        if (!isset($this->urls[$key])) {
            throw new ConfigException("URL não configurada: {$key}");
        }

        return $this->urls[$key];
    }

    #[\Override]
    public function getOperation(string $key): string
    {
        if (!isset($this->operations[$key])) {
            throw new ConfigException("Operação não configurada: {$key}");
        }

        return $this->operations[$key];
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    #[\Override]
    public function getVersion(): string
    {
        $version = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);

        return $version ?? 'dev-unknown';
    }

}