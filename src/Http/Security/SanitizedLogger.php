<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Http\Security;

use Psr\Log\AbstractLogger;

/**
 * Logger PSR-3 que sanitiza dados sensíveis (CPF, CNPJ, e-mail, chaves) antes de
 * escrever. Estende AbstractLogger: basta implementar log(); os 8 métodos de nível
 * (info/warning/error/...) são delegados para cá.
 *
 * Injete no lugar do NullLogger quando precisar de rastreabilidade em produção
 * sem vazar dados sensíveis. A escrita real é feita pela Closure $writer injetada.
 */
final class SanitizedLogger extends AbstractLogger
{
    private SensitiveDataSanitizer $sanitizer;

    public function __construct(private readonly \Closure $writer)
    {
        $this->sanitizer = new SensitiveDataSanitizer();
    }

    /**
     * @param mixed $level
     * @param array<array-key, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelLabel = is_scalar($level) ? strtoupper((string) $level) : 'LOG';

        $safe = sprintf(
            '[%s] [%s] %s%s',
            date('Y-m-d H:i:s'),
            $levelLabel,
            $this->sanitizer->sanitize((string) $message),
            $context !== [] ? ' ' . json_encode($this->sanitizer->sanitize($context), JSON_UNESCAPED_UNICODE) : '',
        );

        ($this->writer)($safe);
    }
}
