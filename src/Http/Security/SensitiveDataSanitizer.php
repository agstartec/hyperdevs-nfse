<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Http\Security;

final class SensitiveDataSanitizer
{
    private const SENSITIVE_KEYS = [
        'senha', 'password', 'privatekey', 'pfx', 'certificado',
        'certificate', 'token', 'authorization', 'secret', 'apikey',
    ];

    /** @var array<string, string> */
    private const PATTERNS = [
        // CPF sem máscara: 11 dígitos isolados
        '/(?<!\d)\d{11}(?!\d)/' => '***********',
        // CNPJ sem máscara: 14 dígitos isolados
        '/(?<!\d)\d{14}(?!\d)/' => '**************',
        // CPF com máscara: 000.000.000-00
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
        // CNPJ com máscara: 00.000.000/0000-00
        '/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/' => '**.***.***/****-**',
        // Chave de acesso NFS-e (50 dígitos)
        '/(?<!\d)\d{50}(?!\d)/' => '**************************************************',
        // E-mail
        '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/' => '***@***',
    ];

    public function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        if (is_array($data)) {
            return $this->sanitizeArray($data);
        }

        return $data;
    }

    private function sanitizeString(string $value): string
    {
        foreach (self::PATTERNS as $pattern => $replacement) {
            $value = (string) preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        /** @var array<array-key, mixed> $result */
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $result[$key] = '[REDACTED]';
            } else {
                $result[$key] = $this->sanitize($value);
            }
        }

        return $result;
    }
}
