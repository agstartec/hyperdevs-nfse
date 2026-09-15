<?php
namespace Hyperevs\Nfse\Config\Contract;
use Hyperevs\Nfse\Domain\Enum\TipoAmbiente;

interface ConfigInterface
{
    public function getTipoAmbiente(): TipoAmbiente;
    public function getTipoApi(): string;
    public function getUrl(string $key): string;
    public function getOperation(string $key): string;
    public function get(string $key, mixed $default = null): mixed;
    public function getVersion(): string;
}