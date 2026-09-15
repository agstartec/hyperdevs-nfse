<?php

declare(strict_types=1);

namespace Hyperevs\Nfse\Repository;

use Hyperevs\Nfse\Domain\Contract\CstClassTribRepository;
use Hyperevs\Nfse\Domain\ValueObject\CstClassTribProperties;

final class InMemoryCstClassTribRepository implements CstClassTribRepository
{
    /** @var array<string, CstClassTribProperties> */
    private array $properties;

    /** @param array<string, CstClassTribProperties>|null $properties */
    public function __construct(?array $properties = null)
    {
        $this->properties = $properties ?? self::defaultData();
    }

    public function findByCode(string $cClassTrib): ?CstClassTribProperties
    {
        return $this->properties[$cClassTrib] ?? null;
    }

    public function findByCst(string $cst): array
    {
        return array_values(
            array_filter(
                $this->properties,
                fn (CstClassTribProperties $p) => $p->getCst() === $cst,
            )
        );
    }

    public function findValidosParaNfse(): array
    {
        return array_values(
            array_filter(
                $this->properties,
                fn (CstClassTribProperties $p) => $p->isValidoParaNfse(),
            )
        );
    }

    /**
     * @return array<string, CstClassTribProperties>
     */
    public static function defaultData(): array
    {
        $data = [];

        foreach (self::rawDefaultData() as $row) {
            $data[$row[0]] = new CstClassTribProperties(...$row);
        }

        return $data;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: bool, 4: bool, 5: bool, 6: ?float, 7: ?float}>
     */
    public static function rawDefaultData(): array
    {
        return [
            // cClassTrib, cst,  desc,                                                                                   validoNfse, permiteDif, exigeTribReg, pRedIBS, pRedCBS
            ['000001', '000', 'Situações tributadas integralmente pelo IBS e CBS.',                                        true,  false, false, null,  null],
            ['011003', '011', 'Intermediação de planos de assistência à saúde',                                          true,  false, false, null,  null],
            ['200016', '200', 'Prestação de serviços de pesquisa e desenvolvimento por Instituição Científica, Tecnológica e de Inovação (ICT)', true, false, false, null, null],
            ['200021', '200', 'Serviços de transporte público coletivo de passageiros ferroviário e hidroviário',       true,  false, false, null,  null],
            ['200025', '200', 'Fornecimento dos serviços de educação relacionados ao Programa Universidade para Todos (Prouni)', true, false, false, null, null],
            ['200026', '200', 'Locação de imóveis localizados nas zonas reabilitadas',                                  true,  false, false, null,  null],
            ['200027', '200', 'Operações de locação, cessão onerosa e arrendamento de bens imóveis',                     true,  false, false, null,  null],
            ['200028', '200', 'Fornecimento dos serviços de educação (Anexo II)',                                        true,  false, false, null,  null],
            ['200037', '200', 'Fornecimento de serviços ambientais de conservação ou recuperação da vegetação nativa',  true,  false, false, null,  null],
            ['200038', '200', 'Fornecimento dos insumos agropecuários e aquícolas (Anexo IX)',                          true,  false, false, null,  null],
            ['200039', '200', 'Fornecimento dos serviços e o licenciamento ou cessão dos direitos destinados às produções nacionais artísticas (Anexo X)', true, false, false, null, null],
            ['200040', '200', 'Fornecimento de serviços de comunicação institucional à administração pública',         true,  false, false, null,  null],
            ['200041', '200', 'Fornecimento de serviço de educação desportiva (art. 141. I)',                           true,  false, false, null,  null],
            ['200042', '200', 'Fornecimento de serviço de educação desportiva (art. 141. II)',                          true,  false, false, null,  null],
            ['200043', '200', 'Fornecimento à administração pública dos serviços e dos bens relativos à soberania (Anexo XI)', true, false, false, null, null],
            ['200044', '200', 'Operações e prestações de serviços de segurança da informação e segurança cibernética desenvolvidos por sociedade que tenha sócio brasileiro (Anexo XI)', true, false, false, null, null],
            ['200045', '200', 'Operações relacionadas a projetos de reabilitação urbana de zonas históricas e de áreas críticas de recuperação e reconversão urbanística', true, false, false, null, null],
            ['200046', '200', 'Operações com bens imóveis',                                                            true,  false, false, null,  null],
            ['200048', '200', 'Hotelaria, Parques de Diversão e Parques Temáticos',                                    true,  false, false, null,  null],
            ['200051', '200', 'Agências de Turismo',                                                                    true,  false, false, null,  null],
            ['200052', '200', 'Prestação de serviços de profissões intelectuais',                                       true,  false, false, null,  null],
            ['400001', '400', 'Fornecimento de serviços de transporte público coletivo de passageiros rodoviário e metroviário', true, false, false, null, null],
            ['820001', '820', 'Documento com informações de fornecimento de serviços de planos de assistência à saúde',  false, false, false, null,  null],
            ['820002', '820', 'Documento com informações de fornecimento de serviços de planos de assistência funerária', false, false, false, null,  null],
            ['820003', '820', 'Documento com informações de fornecimento de serviços de planos de assistência à saúde de animais domésticos', false, false, false, null, null],
            ['820006', '820', 'Documento com informações de fornecimento de serviços de exploração de via',            false, false, false, null,  null],
            ['820007', '820', 'Documento com informações de fornecimento de serviços financeiros',                       false, false, false, null,  null],
        ];
    }
}
