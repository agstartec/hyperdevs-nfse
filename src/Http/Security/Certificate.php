<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Security;

use Hyperevs\Nfse\Http\Security\Exception\CertificateException;

/**
 * Certificado digital A1 (PKCS#12), lido e manipulado apenas com ext-openssl.
 * Substitui \NFePHP\Common\Certificate sem depender do pacote nfephp-org.
 */
final class Certificate
{
    public function __construct(
        private readonly string $privateKeyPem,
        private readonly string $certificatePem,
        private readonly string $chainPem = '',
    ) {
    }

    public static function readPfx(string $pfxContent, string $password): self
    {
        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new CertificateException('Falha ao ler o certificado PFX: senha incorreta ou arquivo inválido');
        }

        $chain = '';
        foreach ($certs['extracerts'] ?? [] as $extraCert) {
            $chain .= $extraCert;
        }

        return new self($certs['pkey'], $certs['cert'], $chain);
    }

    public function privateKeyPem(): string
    {
        return $this->privateKeyPem;
    }

    public function certificatePem(): string
    {
        return $this->certificatePem;
    }

    public function chainPem(): string
    {
        return $this->chainPem;
    }

    /** Conteúdo do certificado em base64 puro (sem cabeçalho/rodapé/quebras), usado dentro de <X509Certificate>. */
    public function certificatePemUnformatted(): string
    {
        return trim(str_replace(
            ["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"],
            '',
            $this->certificatePem,
        ));
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->parsedCertificate()['validFrom_time_t']);
    }

    public function getValidTo(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->parsedCertificate()['validTo_time_t']);
    }

    public function isExpired(): bool
    {
        return $this->getValidTo() < new \DateTimeImmutable();
    }

    public function getCompanyName(): ?string
    {
        return $this->parsedCertificate()['subject']['CN'] ?? null;
    }

    public function sign(string $content, int $algorithm = OPENSSL_ALGO_SHA256): string
    {
        $privateKey = openssl_pkey_get_private($this->privateKeyPem);
        if ($privateKey === false) {
            throw new CertificateException('Falha ao carregar a chave privada do certificado');
        }

        if (!openssl_sign($content, $signature, $privateKey, $algorithm)) {
            throw new CertificateException('Falha ao assinar conteúdo com a chave privada do certificado');
        }

        return $signature;
    }

    /** @return array<string, mixed> */
    private function parsedCertificate(): array
    {
        $parsed = openssl_x509_parse($this->certificatePem);
        if ($parsed === false) {
            throw new CertificateException('Falha ao interpretar o certificado X.509');
        }

        return $parsed;
    }
}
