<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\UnitSummaryDTO;
use PDO;
use PDOException;

final class GetUnitsInCondominiumQueryHandler
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return UnitSummaryDTO[]
     */
    public function handle(GetUnitsInCondominiumQuery $query): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, identifier, owner_id
             FROM units
             WHERE condominium_id = :condominium_id
             ORDER BY identifier ASC" // Consistent ordering
        );

        $dtos = [];
        try {
            $stmt->execute([':condominium_id' => $query->condominiumId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row === false) continue;
                $dtos[] = new UnitSummaryDTO(
                    $row['id'],
                    $row['identifier'],
                    $row['owner_id'] // This will be null if owner_id is NULL in DB, which is correct
                );
            }
        } catch (PDOException $e) {
            // Log error $e->getMessage()
            throw new \RuntimeException("Database error while fetching units for condominium.", 0, $e);
        }

        return $dtos;
    }
}
