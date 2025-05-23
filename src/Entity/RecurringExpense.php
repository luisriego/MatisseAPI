<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\RecurringExpenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecurringExpenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RecurringExpense
{
    public const string FREQUENCY_MONTHLY = 'MONTHLY';
    public const string FREQUENCY_BIMONTHLY = 'BIMONTHLY';
    public const string FREQUENCY_QUARTERLY = 'QUARTERLY';
    public const string FREQUENCY_SEMIANNUALLY = 'SEMIANNUALLY';
    public const string FREQUENCY_ANNUALLY = 'ANNUALLY';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $amount = null;

    #[ORM\ManyToOne]
    private ?ExpenseType $expenseType = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $frequency = null;

    #[ORM\Column(nullable: true)]
    private ?int $dueDay = null;

    #[ORM\Column(nullable: true)]
    private ?array $monthsOfYear = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?int $occurrencesLeft = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'recurringExpense', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $expenses;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: "account_id", referencedColumnName: "id", nullable: false)]
    private ?Account $account = null;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable();
        $this->isActive = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->expenses = new ArrayCollection();
    }

    public static function create(
        string $id,
        string $description,
        ?int $amount,
        ExpenseType $expenseType,
        Account $account,
        string $frequency,
        int $dueDay,
        ?array $monthsOfYear,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?int $occurrencesLeft,
        bool $isActive,
        ?string $notes
    ): self
    {
        $instance = new self();

        $instance->id = $id;
        $instance->description = $description;
        $instance->amount = $amount;
        $instance->expenseType = $expenseType;
        $instance->account = $account;
        $instance->frequency = $frequency;
        $instance->dueDay = $dueDay;
        $instance->monthsOfYear = $monthsOfYear;
        $instance->startDate = $startDate;
        $instance->endDate = $endDate;
        $instance->occurrencesLeft = $occurrencesLeft;
        $instance->isActive = $isActive;
        $instance->notes = $notes;

        return $instance;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function amount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function expenseType(): ?ExpenseType
    {
        return $this->expenseType;
    }

    public function setExpenseType(?ExpenseType $expenseType): static
    {
        $this->expenseType = $expenseType;

        return $this;
    }

    public function frequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function dueDay(): ?int
    {
        return $this->dueDay;
    }

    public function setDueDay(?int $dueDay): static
    {
        $this->dueDay = $dueDay;

        return $this;
    }

    public function monthsOfYear(): ?array
    {
        return $this->monthsOfYear;
    }

    public function setMonthsOfYear(?array $monthsOfYear): static
    {
        $this->monthsOfYear = $monthsOfYear;

        return $this;
    }

    public function startDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function endDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function occurrencesLeft(): ?int
    {
        return $this->occurrencesLeft;
    }

    public function setOccurrencesLeft(?int $occurrencesLeft): static
    {
        $this->occurrencesLeft = $occurrencesLeft;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
            $expense->setRecurringExpense($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): static
    {
        if ($this->expenses->removeElement($expense)) {
            if ($expense->recurringExpense() === $this) {
                $expense->setRecurringExpense(null);
            }
        }

        return $this;
    }

    public function account(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;
        return $this;
    }
}
