<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Account;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class AccountFixtures extends Fixture
{
    public const ACCOUNT_PRINCIPAL_CODE = 'PRINCIPAL';
    public const ACCOUNT_RESERVA_CODE = 'RESERVA';
    public const ACCOUNT_OBRA_CODE = 'OBRA';
    public const ACCOUNT_GAS_CODE = 'GAS';

    public function load(ObjectManager $manager): void
    {
        echo "Loading Accounts...\n";

        $accountsData = [
            self::ACCOUNT_PRINCIPAL_CODE => [
                'name' => 'Conta Principal',
                'description' => 'Conta para movimentação das despesas e receitas ordinárias do condomínio.'
            ],
            self::ACCOUNT_RESERVA_CODE => [
                'name' => 'Fundo de Reserva',
                'description' => 'Fundo destinado a cobrir despesas emergenciais e manutenção futura.'
            ],
            self::ACCOUNT_OBRA_CODE => [
                'name' => 'Fundo de Obra',
                'description' => 'Fundo específico para custear obras aprovadas em assembleia.'
            ],
            self::ACCOUNT_GAS_CODE => [
                'name' => 'Fundo do Gás',
                'description' => 'Fundo específico para gestionar o consumo e reposićão do gás.'
            ],
        ];

        foreach ($accountsData as $code => $data) {
            $existingAccount = $manager->getRepository(Account::class)->findOneBy(['code' => $code]);

            if (!$existingAccount) {
                $account = Account::createWithDescription(
                    Uuid::v4()->toRfc4122(),
                    $code,
                    $data['name'],
                    $data['description']
                );

                $manager->persist($account);
                echo "  - Added Account: $code - {$data['name']}\n";

                $this->addReference('account_' . $code, $account);
            } else {
                echo "  - Skipping Account (already exists): $code - {$data['name']}\n";

                $this->addReference('account_' . $code, $existingAccount);
            }
        }

        $manager->flush();
        echo "Accounts loaded!\n";
    }
}