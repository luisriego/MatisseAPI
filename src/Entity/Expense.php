<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExpenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Expense
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 255, nullable: true,)]
    private ?string $description = "";

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $paidAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    private ?Account $account = null;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    private ?ExpenseType $type = null;

    #[ORM\ManyToOne(targetEntity: RecurringExpense::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(name: "recurring_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?RecurringExpense $recurringExpense = null;

    public function __construct(
        string $id,
        int $amount,
        ExpenseType $type,
        ?Account $account,
        \DateTime $dueDate)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        $this->type = $type;
        $this->account = $account;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $id,
        int $amount,
        ExpenseType $type,
        ?Account $account,
        \DateTime $dueDate): self
    {
        return new self($id, $amount, $type, $account, $dueDate);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function dueDate(): \DateTime
    {
        return $this->dueDate;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function payedAt(\DateTimeImmutable $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function account(): ?Account
    {
        return $this->account;
    }

    public function type(): ?ExpenseType
    {
        return $this->type;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function addDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function recurringExpense(): ?RecurringExpense
    {
        return $this->recurringExpense;
    }

    public function setRecurringExpense(?RecurringExpense $recurringExpense): self
    {
        $this->recurringExpense = $recurringExpense;
        return $this;
    }

    public function setAccount(?string $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @param \DateTime $dueDate
     */
    public function setDueDate(\DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }
}
