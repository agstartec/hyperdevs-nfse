<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Http\Security\Exception;

/**
 * @deprecated desde v2.3.1. A lib não bloqueia mais por proximidade de vencimento
 *             (só por certificado efetivamente vencido, via CertificateExpiredException).
 *             Esta exceção não é mais lançada e será removida numa versão major futura.
 *             Monitorar a janela de renovação cabe ao integrador, via
 *             getCertificate()->getValidTo().
 */
class CertificateExpiringException extends CertificateException
{
}
