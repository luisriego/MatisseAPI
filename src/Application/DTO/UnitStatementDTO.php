<?php

declare(strict_types=1);

namespace App\Application\DTO;

// Requires UnitLedgerAccountDetailsDTO to be available
// Requires FeeIssuedDTO to be available
// Requires PaymentReceivedDTO to be available

final class UnitStatementDTO
{
    public string $unitId;
    public UnitLedgerAccountDetailsDTO $currentBalance;
    /** @var FeeIssuedDTO[] */
    public array $feesIssued;
    /** @var PaymentReceivedDTO[] */
    public array $paymentsReceived;

    /**
     * @param string $unitId
     * @param UnitLedgerAccountDetailsDTO $currentBalance
     * @param FeeIssuedDTO[] $feesIssued
     * @param PaymentReceivedDTO[] $paymentsReceived
     */
    public function __construct(
        string $unitId,
        UnitLedgerAccountDetailsDTO $currentBalance,
        array $feesIssued,
        array $paymentsReceived
    ) {
        $this->unitId = $unitId;
        $this->currentBalance = $currentBalance;
        $this->feesIssued = $feesIssued;
        $this->paymentsReceived = $paymentsReceived;
    }
}
