<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Xml\Builder;

use Hyperdevs\Nfse\Domain\Entity\Evento;
use Hyperdevs\Nfse\Domain\Enum\TipoEvento;
use NFePHP\Common\DOMImproved as Dom;

class EventoXmlBuilder implements Contract\XmlBuilderInterface
{
    private Dom $dom;

    public function __construct()
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    #[\Override]
    public function build(object $entity): string
    {
        if (!$entity instanceof Evento) {
            throw new \InvalidArgumentException('Entity must be an instance of Evento');
        }

        $this->reset();

        $pedRegEvento = $this->dom->createElement('pedRegEvento');
        $pedRegEvento->setAttribute('versao', '1.01');
        $pedRegEvento->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infPedReg = $this->dom->createElement('infPedReg');
        $infPedReg->setAttribute('Id', $this->gerarIdPedRegEvento($entity));

        $this->addChild($infPedReg, 'tpAmb', $entity->getTipoAmbiente());
        $this->addChild($infPedReg, 'verAplic', $entity->getVersaoAplicacao());
        $this->addChild($infPedReg, 'dhEvento', $entity->getDataEvento()->format('Y-m-d\TH:i:sP'));

        if ($entity->getCnpjAutor()) {
            $this->addChild($infPedReg, 'CNPJAutor', $entity->getCnpjAutor(), true);
        } elseif ($entity->getCpfAutor()) {
            $this->addChild($infPedReg, 'CPFAutor', $entity->getCpfAutor(), true);
        }

        $this->addChild($infPedReg, 'chNFSe', $entity->getChaveNfse(), true);

        $this->buildEventoEspecifico($infPedReg, $entity);

        $pedRegEvento->appendChild($infPedReg);
        $this->dom->appendChild($pedRegEvento);

        $xml = $this->dom->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Failed to generate XML');
        }

        return $xml;
    }

    private function buildEventoEspecifico(\DOMNode $parent, Evento $evento): void
    {
        $tipo = $evento->getTipo();
        $tag = $tipo->eventTypeTag();

        $evtNode = $this->dom->createElement($tag);
        $parent->appendChild($evtNode);

        $this->addChild($evtNode, 'xDesc', $tipo->xDesc(), true);

        match ($tipo) {
            TipoEvento::CANCELAMENTO,
            TipoEvento::SOLICITACAO_ANALISE_FISCAL => $this->buildMotivoCancelamento($evtNode, $evento),

            TipoEvento::SUBSTITUICAO => $this->buildSubstituicao($evtNode, $evento),

            TipoEvento::CANCELAMENTO_DEFERIDO => $this->buildDeferido($evtNode, $evento),
            TipoEvento::CANCELAMENTO_INDEFERIDO => $this->buildIndeferido($evtNode, $evento),

            TipoEvento::CONFIRMACAO_PRESTADOR,
            TipoEvento::CONFIRMACAO_TOMADOR,
            TipoEvento::CONFIRMACAO_INTERMEDIARIO,
            TipoEvento::CONFIRMACAO_TACITA => null,

            TipoEvento::REJEICAO_PRESTADOR,
            TipoEvento::REJEICAO_TOMADOR,
            TipoEvento::REJEICAO_INTERMEDIARIO => $this->buildRejeicao($evtNode, $evento),

            TipoEvento::ANULACAO_REJEICAO => $this->buildAnulacaoRejeicao($evtNode, $evento),

            TipoEvento::CANCELAMENTO_OFICIO => $this->buildCancelamentoOficio($evtNode, $evento),

            TipoEvento::BLOQUEIO_OFICIO => $this->buildBloqueioOficio($evtNode, $evento),

            TipoEvento::DESBLOQUEIO_OFICIO => $this->buildDesbloqueioOficio($evtNode, $evento),
        };
    }

    private function buildMotivoCancelamento(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCodigoMotivo()) {
            $this->addChild($parent, 'cMotivo', $evento->getCodigoMotivo(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), true);
        }
    }

    private function buildSubstituicao(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCodigoMotivo()) {
            $this->addChild($parent, 'cMotivo', $evento->getCodigoMotivo(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), false);
        }
        if ($evento->getChSubstituta()) {
            $this->addChild($parent, 'chSubstituta', $evento->getChSubstituta(), true);
        }
    }

    private function buildDeferido(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getNProcAdm()) {
            $this->addChild($parent, 'nProcAdm', $evento->getNProcAdm(), false);
        }
        if ($evento->getCodigoMotivo()) {
            $this->addChild($parent, 'cMotivo', $evento->getCodigoMotivo(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), true);
        }
    }

    private function buildIndeferido(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getNProcAdm()) {
            $this->addChild($parent, 'nProcAdm', $evento->getNProcAdm(), false);
        }
        if ($evento->getCodigoMotivo()) {
            $this->addChild($parent, 'cMotivo', $evento->getCodigoMotivo(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), true);
        }
    }

    private function buildRejeicao(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCodigoMotivo()) {
            $this->addChild($parent, 'cMotivo', $evento->getCodigoMotivo(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), false);
        }
    }

    private function buildAnulacaoRejeicao(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getIdEvManifRej()) {
            $this->addChild($parent, 'idEvManifRej', $evento->getIdEvManifRej(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), true);
        }
    }

    private function buildCancelamentoOficio(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getNProcAdm()) {
            $this->addChild($parent, 'nProcAdm', $evento->getNProcAdm(), true);
        }
        if ($evento->getXProcAdm()) {
            $this->addChild($parent, 'xProcAdm', $evento->getXProcAdm(), true);
        }
    }

    private function buildBloqueioOficio(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getCodEventoBloqueio()) {
            $this->addChild($parent, 'codEvento', $evento->getCodEventoBloqueio(), true);
        }
        if ($evento->getDescricaoMotivo()) {
            $this->addChild($parent, 'xMotivo', $evento->getDescricaoMotivo(), true);
        }
    }

    private function buildDesbloqueioOficio(\DOMNode $parent, Evento $evento): void
    {
        if ($evento->getCpfAgTrib()) {
            $this->addChild($parent, 'CPFAgTrib', $evento->getCpfAgTrib(), true);
        }
        if ($evento->getIdBloqOfic()) {
            $this->addChild($parent, 'idBloqOfic', $evento->getIdBloqOfic(), true);
        }
    }

    private function gerarIdPedRegEvento(Evento $evento): string
    {
        return 'PRE'
            . $evento->getChaveNfse()
            . $evento->getTipo()->value;
    }

    private function reset(): void
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    private function addChild(\DOMNode $parent, string $name, mixed $value, bool $required = true): ?\DOMElement
    {
        if (!$required && ($value === null || $value === '')) {
            return null;
        }

        $element = $this->dom->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);

        return $element;
    }
}
