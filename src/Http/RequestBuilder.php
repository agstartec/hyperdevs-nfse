<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http;

class RequestBuilder
{

    public function buildGzipBase64Payload(string $xml, string $fieldName): array
    {
        $gzipped = gzencode($xml);
        if ($gzipped === false) {
            throw new \RuntimeException('Failed to gzip encode XML');
        }
        $base64 = base64_encode($gzipped);
        return [$fieldName => $base64];
    }


    public function buildDpsPayload(string $xml): array
    {
        return $this->buildGzipBase64Payload($xml, 'dpsXmlGZipB64');
    }

    public function buildEventoPayload(string $xml): array
    {
        return $this->buildGzipBase64Payload($xml, 'pedidoRegistroEventoXmlGZipB64');
    }
}