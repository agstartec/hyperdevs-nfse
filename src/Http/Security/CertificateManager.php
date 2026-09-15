<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Security;

use Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface;
use Hyperevs\Nfse\Http\Security\Exception\CertificateException;
use Hyperevs\Nfse\Http\Security\Exception\CertificateExpiredException;

final class CertificateManager implements CertificateManagerInterface
{
    /** @var array{private: string, public: string, cert: string}|null */
    private ?array $tempFiles = null;

    public function __construct(private readonly Certificate $certificate)
    {
        if ($this->certificate->isExpired()) {
            throw new CertificateExpiredException(
                "Certificado vencido em {$this->certificate->getValidTo()->format('d/m/Y')}"
            );
        }
    }

    public static function fromPfx(string $pfxContent, string $password): self
    {
        return new self(Certificate::readPfx($pfxContent, $password));
    }

    public static function fromPfxFile(string $path, string $password): self
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new CertificateException("Não foi possível ler o arquivo de certificado: {$path}");
        }

        return self::fromPfx($content, $password);
    }

    #[\Override]
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    /**
     * Materializa os PEMs em arquivos temporários (0600), reusados em chamadas subsequentes.
     * São apagados no __destruct — mantenha este manager vivo enquanto durar o uso do cURL.
     */
    #[\Override]
    public function saveTemporaryFiles(): array
    {
        if ($this->tempFiles !== null) {
            return $this->tempFiles;
        }

        $privatePath = tempnam(sys_get_temp_dir(), 'nfse_key_');
        $certPath = tempnam(sys_get_temp_dir(), 'nfse_cert_');

        if ($privatePath === false || $certPath === false) {
            throw new CertificateException('Falha ao criar arquivos temporários do certificado');
        }

        file_put_contents($privatePath, $this->certificate->privateKeyPem());
        file_put_contents($certPath, $this->certificate->certificatePem() . $this->certificate->chainPem());
        chmod($privatePath, 0600);
        chmod($certPath, 0600);

        return $this->tempFiles = [
            'private' => $privatePath,
            'public' => $certPath,
            'cert' => $certPath,
        ];
    }

    public function __destruct()
    {
        foreach (array_unique($this->tempFiles ?? []) as $path) {
            if (is_string($path) && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
