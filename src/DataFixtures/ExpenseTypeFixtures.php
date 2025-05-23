<?php

namespace App\DataFixtures;

use App\Entity\ExpenseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExpenseTypeFixtures extends Fixture
{
    private const EXPENSE_TYPES = [
        // Manutenção e Reparos (MR)
        'MR1GE' => ['name' => 'MANUTENCAO_GERAL', 'description' => 'Pequenos reparos (hidráulica, elétrica em áreas comuns, chaveiro, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR2EV' => ['name' => 'MANUTENCAO_ELEVADOR', 'description' => 'Contratos de manutenção, revisões periódicas, peças, reparos emergenciais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'MR3JA' => ['name' => 'JARDINAGEM_PAISAGISMO', 'description' => 'Corte de grama, poda de árvores/arbustos, adubação, controle de pragas de jardim, irrigação.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR4PR' => ['name' => 'MANUTENCAO_PREDIAL', 'description' => 'Pintura de áreas comuns (corredores, hall), reparos em alvenaria/pisos comuns, limpeza de fachada/garagem.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR5EQ' => ['name' => 'MANUTENCAO_EQUIPAMENTOS', 'description' => 'Bombas d\'água, portões eletrônicos, interfones, sistema de CFTV (câmeras).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR6SI' => ['name' => 'MANUTENCAO_SISTEMAS_INCENDIO', 'description' => 'Recarga/revisão de extintores, teste de mangueiras, manutenção de alarmes e detectores.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR7CP' => ['name' => 'CONTROLE_PRAGAS', 'description' => 'Dedetizações periódicas ou emergenciais (baratas, ratos, cupins, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        // Serviços Públicos / Contas de Consumo (SP)
        'SP1EL' => ['name' => 'CEMIG', 'description' => 'Conta de luz de corredores, elevador(es), bombas, portões, iluminação externa.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP2AG' => ['name' => 'COPASA', 'description' => 'Conta de água/esgoto para limpeza, jardinagem (se não individualizada), consumo da portaria.', 'distributionMethod' => 'FRACTION', 'isRecurring' => true],
        'SP3GA' => ['name' => 'GAS_TOTAL_A_COMPENSAR', 'description' => 'Total a compensar pelas unidades consumidoras', 'distributionMethod' => 'INDIVIDUAL', 'isRecurring' => true],
        'SP4TC' => ['name' => 'INTERNET_DO_CFTV', 'description' => 'Linha telefônica/internet', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Pessoal / Folha de Pagamento (PF)
        'PF1SE' => ['name' => 'SALARIOS_ENCARGOS', 'description' => 'Taxa mensal do síndico', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Terceirizados (ST)
        'ST1LT' => ['name' => 'LIMPEZA_TERCEIRIZADA', 'description' => 'Contrato com empresa de limpeza.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'ST2AJ' => ['name' => 'ASSESSORIA_JURIDICA_CONTABIL', 'description' => 'Honorários de advogados, contadores, auditorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        // Administrativo e Financeiro (AF)
        'AF1DB' => ['name' => 'DESPESAS_BANCARIAS', 'description' => 'Tarifas de manutenção de conta, taxas de boletos.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'AF2SG' => ['name' => 'SEGUROS_CONDOMINIO', 'description' => 'Seguro obrigatório do condomínio (incêndio, etc.), seguro de responsabilidade civil do síndico.', 'distributionMethod' => 'FRACTION', 'isRecurring' => false],
        'AF3ML' => ['name' => 'MATERIAL_LIMPEZA', 'description' => 'Produtos de limpeza, sacos de lixo', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF4IT' => ['name' => 'IMPOSTOS_TAXAS', 'description' => 'IPTU de áreas comuns (se houver), outras taxas municipais/estaduais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF5CC' => ['name' => 'CORREIOS_CARTORIO', 'description' => 'Despesas com envio de correspondência, reconhecimento de firmas, cópias autenticadas.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        'OT1DA' => ['name' => 'DESPESAS_ASSEMBLEIA', 'description' => 'Aluguel de espaço (se necessário), cópias de documentos, envio de convocações.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'OT2DD' => ['name' => 'DESPESAS_DIVERSAS', 'description' => 'Gastos menores e eventuais não classificáveis nas outras categorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
    ];

    public function load(ObjectManager $manager): void
    {
        echo "Loading expense Types...\n";

        foreach (self::EXPENSE_TYPES as $code => $data) {
            $existingType = $manager->getRepository(ExpenseType::class)->findOneBy(['code' => $code]);

            if (!$existingType) {
                $expenseType = new ExpenseType();
                $expenseType->setCode($code);
                $expenseType->setName($data['name']);
                $expenseType->setDescription($data['description'] ?? null);
                $expenseType->setdistributionMethod($data['distributionMethod']);
                $expenseType->setIsRecurring($data['isRecurring']);

                $manager->persist($expenseType);
                echo "  - Added: $code - {$data['name']}\n";
            } else {
                echo "  - Skipping (already exists): $code - {$data['name']}\n";
            }
        }

        $manager->flush();
        echo "expense Types loaded!\n";
    }
}
