<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\IncomeType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class IncomeTypeFixtures extends Fixture
{
    private const INCOMING_TYPES = [
        // Receitas Ordinárias
        'RC1TC' => ['name' => 'TAXA_CONDOMINIAL', 'description' => 'Recebimento da cota condominial mensal regular dos condôminos.'],
        'RC2JM' => ['name' => 'JUROS_MULTAS_ATRASO', 'description' => 'Recebimento de juros e multas por pagamento de cotas condominiais em atraso.'],

        // Receitas Extraordinárias
        'RC3AE' => ['name' => 'ALUGUEL_ESPACOS', 'description' => 'Receita proveniente do aluguel de espaços comuns (ex: salão de festas, churrasqueira).'],
        'RC4CE' => ['name' => 'COTA_EXTRA', 'description' => 'Recebimento de cotas extras aprovadas em assembleia para fins específicos (obras, melhorias, fundo específico).'],
        'RC5RB' => ['name' => 'REEMBOLSOS', 'description' => 'Recebimento de valores para cobrir danos causados por condôminos/terceiros, ressarcimento de despesas adiantadas, etc.'],

        // Receitas Financeiras
        'RC6RF' => ['name' => 'RENDIMENTOS_FINANCEIROS', 'description' => 'Juros ou rendimentos de aplicações financeiras do fundo de reserva ou outras contas de investimento do condomínio.'],

        // Outras Receitas
        'RC7DV' => ['name' => 'RECEITAS_DIVERSAS', 'description' => 'Outras receitas eventuais não classificadas nas categorias anteriores (ex: venda de materiais recicláveis, multas não relacionadas a atraso de cota, doações).'],
    ];

    public function load(ObjectManager $manager): void
    {
        echo "Loading Income Types...\n";

        foreach (self::INCOMING_TYPES as $code => $data) {
            $existingType = $manager->getRepository(IncomeType::class)->findOneBy(['code' => $code]);

            if (!$existingType) {
                $incomeType = new IncomeType();

                $incomeType->setCode($code);
                $incomeType->setName($data['name']);
                $incomeType->setDescription($data['description'] ?? null);

                $manager->persist($incomeType);
                echo "  - Added: $code - {$data['name']}\n";
            } else {
                echo "  - Skipping (already exists): $code - {$data['name']}\n";
            }
        }

        $manager->flush();
        echo "Income Types loaded!\n";
    }
}