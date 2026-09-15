<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Client;

use Hyperevs\Nfse\Http\Contract\HttpClientInterface;
use Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperevs\Nfse\Http\Client\Exception\ConnectionException;
use Hyperevs\Nfse\Http\Client\Exception\TimeoutException;

class CurlHttpClient implements HttpClientInterface{
    private const DEFAULT_TIMEOUT = 60;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    private ?array $certFiles = null;

    public function __construct(
        private int $timeout = self::DEFAULT_TIMEOUT,
        private int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        private ?CertificateManagerInterface $certificateManager = null,
        private ?string $keyPassword = null,
    ) {
    }

    #[\Override]
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    #[\Override]
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    public function post(string $url, mixed $data, array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    #[\Override]
    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    public function head(string $url, array $headers = []): array
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, body: string}
     */
    private function request(string $method, string $url, mixed $data = null, array $headers = []): array
    {
        if ($url === '' || $method === '') {
            throw new ConnectionException('URL and method cannot be empty');
        }

        $ch = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->prepareHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_DEFAULT,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HEADER => false,
        ];

        if ($data !== null) {
            $postData = is_array($data) ? json_encode($data) : $data;
            if ($postData !== '' && $postData !== false) {
                $options[CURLOPT_POSTFIELDS] = $postData;
            }
        }

        if ($this->certificateManager !== null) {
            $this->certFiles ??= $this->certificateManager->saveTemporaryFiles();

            $certPath = $this->certFiles['cert'];
            $keyPath = $this->certFiles['private'];
            if ($certPath === '' || $keyPath === '') {
                throw new ConnectionException('Falha ao materializar os arquivos do certificado cliente');
            }

            $options[CURLOPT_SSLCERT] = $certPath;
            $options[CURLOPT_SSLKEY] = $keyPath;
        }

        if ($this->keyPassword !== null && $this->keyPassword !== '') {
            $options[CURLOPT_KEYPASSWD] = $this->keyPassword;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new TimeoutException("Request timeout after {$this->timeout}s");
        }

        if ($errno !== 0) {
            throw new ConnectionException("CURL Error [{$errno}]: {$error}");
        }

        if ($response === false || $response === true) {
            throw new ConnectionException('Request failed with invalid response');
        }

        return [
            'status' => $httpCode,
            'body' => $response,
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array<int, string>
     */
    private function prepareHeaders(array $headers): array
    {
        $temContentType = false;
        $prepared = [];

        foreach ($headers as $key => $value) {
            if (strcasecmp($key, 'Content-Type') === 0) {
                $temContentType = true;
            }
            $prepared[] = "{$key}: {$value}";
        }

        if (!$temContentType) {
            array_unshift($prepared, 'Content-Type: application/json');
        }

        return $prepared;
    }
    
}