<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IncomeTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: IncomeTypeRepository::class)]
class IncomeType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 6)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'type', orphanRemoval: true)]
    private Collection $incomes;

    public function __construct()
    {
        $this->incomes = new ArrayCollection();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function setName(string $name):static
    {
        $this->name = $name;

        return $this;
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

    public function incomes(Income $income): Collection
    {
        return $this->incomes;
    }

//    public function addIncome(Income $income): void
//    {
//        if ($this->incomes->contains)
//    }
}
