<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[HasLifecycleCallbacks]
class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(length: 16, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    /**
     * @var Collection<int, Expense>
     */
    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'account')]
    private Collection $expenses;

    private function __construct(string $id, string $code, string $name)
    {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->isActive = false;
        $this->createdAt = new \DateTimeImmutable();
//        $this->markAsUpdated(); // I'm thinking if is necessary at the Account Construction Time or only at update time.
$this->expenses = new ArrayCollection();
    }

    public static function create(string $id, string $code, string $name): self
    {
        return new self($id, $code, $name);
    }

    public static function createWithDescription(string $id, string $code, string $name, string $description): self
    {
        $account = new self($id, $code, $name);
        $account->setDescription($description);

        return $account;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
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

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
            $expense->setAccount($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): static
    {
        if ($this->expenses->removeElement($expense)) {
            // set the owning side to null (unless already changed)
            if ($expense->getAccount() === $this) {
                $expense->setAccount(null);
            }
        }

        return $this;
    }
}
