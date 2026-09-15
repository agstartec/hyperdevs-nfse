<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Contract;

interface ApiConnectorInterface
{
    public function get(string $endpoint, array $params = []): array;
    public function post(string $endpoint, array $data): array;
    public function head(string $endpoint): array;
}