<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Resident;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class ResidenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        echo "Loading Units...\n";

        $UnitsData = [
            Resident::AP_101 => [
                'name' => 'APARTAMENTO 101'
            ],
            Resident::AP_201 => [
                'name' => 'APARTAMENTO 201'
            ],
            Resident::AP_301 => [
                'name' => 'APARTAMENTO 301'
            ],
            Resident::AP_401 => [
                'name' => 'APARTAMENTO 401'
            ],
            Resident::AP_501 => [
                'name' => 'APARTAMENTO 501'
            ],
            Resident::CONDO => [
                'name' => 'CONDOMINIO'
            ],
        ];

        foreach ($UnitsData as $unitData => $data) {
            $existingUnit = $manager->getRepository(Resident::class)->findOneBy(['unit' => $unitData]);

            if (!$existingUnit) {
                $unit = Resident::create(
                    Uuid::v4()->toRfc4122(),
                    $unitData,
                );

                $manager->persist($unit);
                echo "  - Added Unit: $unitData - {$data['name']}\n";

                $this->addReference('resident_' . $unitData, $unit);
            } else {
                echo "  - Skipping Account (already exists): $unitData - {$data['name']}\n";

                $this->addReference('account_' . $unitData, $existingUnit);
            }
        }

        $manager->flush();
        echo "Units loaded!\n";
    }
}