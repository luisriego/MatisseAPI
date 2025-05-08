<?php

namespace App\Entity;

use App\Repository\IncomeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Income
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;
    
    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 255, nullable: true,)]
    private ?string $description = "empty";

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $paidAt;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: IncomeType::class, inversedBy: 'incomes')]
    private ?IncomeType $type = null;
    
    #[ORM\ManyToOne(targetEntity: Resident::class, inversedBy: 'incomes')] 
    #[ORM\JoinColumn(name: "residence_id", referencedColumnName: "id", nullable: false)] 
    private ?Resident $residence = null;
    
    private function __construct(string $id, int $amount, IncomeType $type, Resident $residence, \DateTime $dueDate)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        $this->type = $type;
        $this->residence = $residence;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $id,
        int $amount,
        IncomeType $type,
        Resident $residence,
        \DateTime $dueDate): self
    {
        return new self($id, $amount, $type, $residence, $dueDate);
    }
    
    public function id(): string
    {
        return $this->id;
    }
    
    public function amount(): ?int
    {
        return $this->amount;
    }

    public function dueDate(): ?\DateTime
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

    public function residence(): ?Resident
    {
        return $this->residence;
    }

    public function type(): ?IncomeType
    {
        return $this->type;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function addDescription(string $description): void
    {
        $this->description = $description;
    }
}
