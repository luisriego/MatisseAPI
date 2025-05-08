<?php

namespace App\DataFixtures;

use App\Entity\ExpenseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExpenseTypeFixtures extends Fixture
{
    private const EXPENSE_TYPES = [
        // Manutenção e Reparos (MR)
        'MR1GE' => ['name' => 'MANUTENCAO_GERAL', 'description' => 'Pequenos reparos (hidráulica, elétrica em áreas comuns, chaveiro, etc.).'],
        'MR2EV' => ['name' => 'MANUTENCAO_ELEVADOR', 'description' => 'Contratos de manutenção, revisões periódicas, peças, reparos emergenciais.'],
        'MR3JA' => ['name' => 'JARDINAGEM_PAISAGISMO', 'description' => 'Corte de grama, poda de árvores/arbustos, adubação, controle de pragas de jardim, irrigação.'],
        'MR4PR' => ['name' => 'MANUTENCAO_PREDIAL', 'description' => 'Pintura de áreas comuns (corredores, hall), reparos em alvenaria/pisos comuns, limpeza de fachada/garagem.'],
        'MR5EQ' => ['name' => 'MANUTENCAO_EQUIPAMENTOS', 'description' => 'Bombas d\'água, portões eletrônicos, interfones, sistema de CFTV (câmeras).'],
        'MR6SI' => ['name' => 'MANUTENCAO_SISTEMAS_INCENDIO', 'description' => 'Recarga/revisão de extintores, teste de mangueiras, manutenção de alarmes e detectores.'],
        'MR7CP' => ['name' => 'CONTROLE_PRAGAS', 'description' => 'Dedetizações periódicas ou emergenciais (baratas, ratos, cupins, etc.).'],

        // Serviços Públicos / Contas de Consumo (SP)
        'SP1EL' => ['name' => 'ELETRICIDADE_AREAS_COMUNS', 'description' => 'Conta de luz de corredores, elevador(es), bombas, portões, iluminação externa.'],
        'SP2AG' => ['name' => 'AGUA_ESGOTO_AREAS_COMUNS', 'description' => 'Conta de água/esgoto para limpeza, jardinagem (se não individualizada), consumo da portaria.'],
        'SP3GA' => ['name' => 'GAS_TOTAL_A_COMPENSAR', 'description' => 'Total a compensar pelas unidades consumidoras'],
        'SP4TC' => ['name' => 'INTERNET_DO_CFTV', 'description' => 'Linha telefônica/internet, manutenção de interfones.'],

        // Pessoal / Folha de Pagamento (PF)
        'PF1SE' => ['name' => 'SALARIOS_ENCARGOS', 'description' => 'Salários de pessoal de limpeza, jardineiros (se funcionários diretos), incluindo 13º, férias, FGTS, INSS.'],
        'PF2UE' => ['name' => 'UNIFORMES_EPI', 'description' => 'Compra de uniformes, botas, luvas e outros Equipamentos de Proteção Individual.'],

        // Serviços Terceirizados (ST)
        'ST1AD' => ['name' => 'ADMINISTRACAO_CONDOMINIO', 'description' => 'Taxa mensal do síndico/administrador.'],
        'ST2LT' => ['name' => 'LIMPEZA_TERCEIRIZADA', 'description' => 'Contrato com empresa de limpeza.'],
        'ST3AJ' => ['name' => 'ASSESSORIA_JURIDICA_CONTABIL', 'description' => 'Honorários de advogados, contadores, auditorias.'],

        // Administrativo e Financeiro (AF)
        'AF1DB' => ['name' => 'DESPESAS_BANCARIAS', 'description' => 'Tarifas de manutenção de conta, taxas de boletos.'],
        'AF2SG' => ['name' => 'SEGUROS_CONDOMINIO', 'description' => 'Seguro obrigatório do condomínio (incêndio, etc.), seguro de responsabilidade civil do síndico.'],
        'AF3ML' => ['name' => 'MATERIAL_ESCRITORIO_LIMPEZA', 'description' => 'Papelaria, cartuchos/toner, produtos de limpeza, sacos de lixo.'],
        'AF4IT' => ['name' => 'IMPOSTOS_TAXAS', 'description' => 'IPTU de áreas comuns (se houver), outras taxas municipais/estaduais.'],
        'AF5CC' => ['name' => 'CORREIOS_CARTORIO', 'description' => 'Despesas com envio de correspondência, reconhecimento de firmas, cópias autenticadas.'],

        // Outros (OT)
        'OT1DA' => ['name' => 'DESPESAS_ASSEMBLEIA', 'description' => 'Aluguel de espaço (se necessário), cópias de documentos, envio de convocações.'],
        'OT2FR' => ['name' => 'FUNDO_RESERVA', 'description' => 'Contribuição mensal definida em assembleia para cobrir despesas emergenciais ou futuras obras.'],
        'OT3FO' => ['name' => 'FUNDO_OBRA', 'description' => 'Contribuição mensal para uma obra específica por tempo claramente definido.'],
        'OT4DD' => ['name' => 'DESPESAS_DIVERSAS', 'description' => 'Gastos menores e eventuais não classificáveis nas outras categorias.'],
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