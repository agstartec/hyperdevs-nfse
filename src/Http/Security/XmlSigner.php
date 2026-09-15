<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Http\Security;

use Hyperevs\Nfse\Http\Security\Contract\XmlSignerInterface;
use Hyperevs\Nfse\Http\Security\Exception\CertificateException;

/**
 * Assinatura XML-DSig (enveloped, C14N, SHA-256) exigida pelo SEFIN Nacional,
 * usando apenas DOMDocument/openssl — sem dependência do NFePHP.
 */
final class XmlSigner implements XmlSignerInterface
{
    private const NS_DSIG = 'http://www.w3.org/2000/09/xmldsig#';
    private const NS_C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const NS_ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const NS_SIGNATURE_METHOD = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const NS_DIGEST_METHOD = 'http://www.w3.org/2001/04/xmlenc#sha256';

    public function __construct(private readonly Certificate $certificate)
    {
    }

    #[\Override]
    public function sign(string $xml, string $tagname, string $rootname): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // LIBXML_NONET mitiga XXE/SSRF (mesmo racional do XsdValidator/NfseXmlParser).
        if (!$dom->loadXML($xml, LIBXML_NONET)) {
            throw new CertificateException('XML inválido para assinatura');
        }

        $root = $dom->getElementsByTagName($rootname)->item(0);
        $node = $dom->getElementsByTagName($tagname)->item(0);

        if ($node === null || $root === null) {
            throw new CertificateException("Tag '{$tagname}' ou raiz '{$rootname}' não encontrada no XML para assinatura");
        }

        $this->appendSignature($dom, $root, $node);

        return $dom->saveXML($dom->documentElement, LIBXML_NOXMLDECL);
    }

    private function appendSignature(\DOMDocument $dom, \DOMNode $root, \DOMElement $node): void
    {
        $id = trim($node->getAttribute('Id'));
        $digestValue = $this->digest($node);

        $signatureNode = $dom->createElementNS(self::NS_DSIG, 'Signature');
        $root->appendChild($signatureNode);

        $signedInfoNode = $dom->createElement('SignedInfo');
        $signatureNode->appendChild($signedInfoNode);

        $canonicalNode = $dom->createElement('CanonicalizationMethod');
        $canonicalNode->setAttribute('Algorithm', self::NS_C14N);
        $signedInfoNode->appendChild($canonicalNode);

        $signatureMethodNode = $dom->createElement('SignatureMethod');
        $signatureMethodNode->setAttribute('Algorithm', self::NS_SIGNATURE_METHOD);
        $signedInfoNode->appendChild($signatureMethodNode);

        $referenceNode = $dom->createElement('Reference');
        $referenceNode->setAttribute('URI', $id !== '' ? "#{$id}" : '');
        $signedInfoNode->appendChild($referenceNode);

        $transformsNode = $dom->createElement('Transforms');
        $referenceNode->appendChild($transformsNode);

        $transformEnveloped = $dom->createElement('Transform');
        $transformEnveloped->setAttribute('Algorithm', self::NS_ENVELOPED);
        $transformsNode->appendChild($transformEnveloped);

        $transformC14n = $dom->createElement('Transform');
        $transformC14n->setAttribute('Algorithm', self::NS_C14N);
        $transformsNode->appendChild($transformC14n);

        $digestMethodNode = $dom->createElement('DigestMethod');
        $digestMethodNode->setAttribute('Algorithm', self::NS_DIGEST_METHOD);
        $referenceNode->appendChild($digestMethodNode);

        $referenceNode->appendChild($dom->createElement('DigestValue', $digestValue));

        $signedInfoCanonical = $signedInfoNode->C14N(true, false, null, null);
        $signatureValue = base64_encode($this->certificate->sign($signedInfoCanonical));

        $signatureNode->appendChild($dom->createElement('SignatureValue', $signatureValue));

        $keyInfoNode = $dom->createElement('KeyInfo');
        $signatureNode->appendChild($keyInfoNode);

        $x509DataNode = $dom->createElement('X509Data');
        $keyInfoNode->appendChild($x509DataNode);

        $x509DataNode->appendChild(
            $dom->createElement('X509Certificate', $this->certificate->certificatePemUnformatted())
        );
    }

    private function digest(\DOMNode $node): string
    {
        $canonical = $node->C14N(true, false, null, null);

        return base64_encode(hash('sha256', $canonical, true));
    }
}
