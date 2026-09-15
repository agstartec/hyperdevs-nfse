<?php

declare(strict_types=1);

namespace Hyperdevs\Nfse\Domain\Enum;

enum MecanismoApoioTomador: string
{
    case DESCONHECIDO = '00';
    case NENHUM = '01';
    case ADM_PUBLICA = '02';
    case ALUGUEIS_ARREND = '03';
    case ARREND_AERONAVE = '04';
    case COMISSAO_AGENTES = '05';
    case DESPESAS_ARMAZEN = '06';
    case EVENTOS_FIFA_SUB = '07';
    case EVENTOS_FIFA = '08';
    case FRETES_ARREND = '09';
    case MATERIAL_AERONAUTICO = '10';
    case PROMOCAO_BENS = '11';
    case PROMOCAO_DEST_TURISTICOS = '12';
    case PROMOCAO_BRASIL = '13';
    case PROMOCAO_SERVICOS = '14';
    case RECINE = '15';
    case RECOPA = '16';
    case REGISTRO_MARCAS = '17';
    case REICOMP = '18';
    case REIDI = '19';
    case REPENEC = '20';
    case REPES = '21';
    case RETAERO = '22';
    case RETID = '23';
    case ROYALTIES = '24';
    case CONFORMIDADE_OMC = '25';
    case ZPE = '26';

    public function descricao(): string
    {
        return match ($this) {
            self::DESCONHECIDO => 'Desconhecido (tipo não informado na nota de origem)',
            self::NENHUM => 'Nenhum',
            self::ADM_PUBLICA => 'Adm. Pública e Repr. Internacional',
            self::ALUGUEIS_ARREND => 'Alugueis e Arrend. Mercantil de maquinas, equip., embarc. e aeronaves',
            self::ARREND_AERONAVE => 'Arrendamento Mercantil de aeronave para empresa de transporte aéreo público',
            self::COMISSAO_AGENTES => 'Comissão a agentes externos na exportação',
            self::DESPESAS_ARMAZEN => 'Despesas de armazenagem, mov. e transporte de carga no exterior',
            self::EVENTOS_FIFA_SUB => 'Eventos FIFA (subsidiária)',
            self::EVENTOS_FIFA => 'Eventos FIFA',
            self::FRETES_ARREND => 'Fretes, arrendamentos de embarcações ou aeronaves e outros',
            self::MATERIAL_AERONAUTICO => 'Material Aeronáutico',
            self::PROMOCAO_BENS => 'Promoção de Bens no Exterior',
            self::PROMOCAO_DEST_TURISTICOS => 'Promoção de Dest. Turísticos Brasileiros',
            self::PROMOCAO_BRASIL => 'Promoção do Brasil no Exterior',
            self::PROMOCAO_SERVICOS => 'Promoção Serviços no Exterior',
            self::RECINE => 'RECINE',
            self::RECOPA => 'RECOPA',
            self::REGISTRO_MARCAS => 'Registro e Manutenção de marcas, patentes e cultivares',
            self::REICOMP => 'REICOMP',
            self::REIDI => 'REIDI',
            self::REPENEC => 'REPENEC',
            self::REPES => 'REPES',
            self::RETAERO => 'RETAERO',
            self::RETID => 'RETID',
            self::ROYALTIES => 'Royalties, Assistência Técnica, Científica e Assemelhados',
            self::CONFORMIDADE_OMC => 'Serviços de avaliação da conformidade vinculados aos Acordos da OMC',
            self::ZPE => 'ZPE',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
