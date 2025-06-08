<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\CondominiumDetailsDTO;
use PDO;
use PDOException;
use DomainException; // For not found

final class GetCondominiumDetailsQueryHandler
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(GetCondominiumDetailsQuery $query): CondominiumDetailsDTO
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, address_street, address_city, address_postal_code, address_country
             FROM condominiums
             WHERE id = :id"
        );

        try {
            $stmt->execute([':id' => $query->condominiumId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error $e->getMessage()
            throw new \RuntimeException("Database error while fetching condominium details.", 0, $e);
        }

        if (!$row) {
            throw new DomainException("Condominium with ID {$query->condominiumId} not found.");
        }

        return new CondominiumDetailsDTO(
            $row['id'],
            $row['name'],
            $row['address_street'],
            $row['address_city'],
            $row['address_postal_code'],
            $row['address_country']
        );
    }
}
