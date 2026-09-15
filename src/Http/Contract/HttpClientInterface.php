<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Http\Contract;

interface HttpClientInterface
{
    public function get(string $url, array $headers = []): array;
    public function post(string $url, mixed $data, array $headers = []): array;
    public function head(string $url, array $headers = []): array;
}