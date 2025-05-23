<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResidentRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResidentRepository::class)]
#[ORM\Table(name: 'resident')]
class Resident
{
    public const AP_101 = 'AP101';
    public const AP_201 = 'AP201';
    public const AP_301 = 'AP301';
    public const AP_401 = 'AP401';
    public const AP_501 = 'AP501';
    public const CONDO = 'CONDO';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(type: 'string', length: 10)]
    private string $unit;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    #[ORM\Column(type: 'float')]
    private float $IdealFraction = 0.0;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'resident', cascade: ['persist'], orphanRemoval: true)]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'residence')]
    private Collection $incomes;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'residence')]
    private Collection $slips;

    private function __construct(string $id, string $unit)
    {
        $this->id = $id;
        $this->unit = $unit;
        $this->createdAt = new \DateTimeImmutable();
        $this->incomes = new ArrayCollection();
        $this->markAsUpdated();
        $this->slips = new ArrayCollection();
    }

    public static function create(string $id, string $unit): self
    {
        return new self($id, $unit);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function idealFraction(): float
    {
        return $this->IdealFraction;
    }

    public function setIdealFraction(float $IdealFraction): void
    {
        $this->IdealFraction = $IdealFraction;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return Collection<int, Slip>
     */
    public function getSlips(): Collection
    {
        return $this->slips;
    }

    public function addSlip(Slip $slip): static
    {
        if (!$this->slips->contains($slip)) {
            $this->slips->add($slip);
            $slip->setResidence($this);
        }

        return $this;
    }

    public function removeSlip(Slip $slip): static
    {
        if ($this->slips->removeElement($slip)) {
            // set the owning side to null (unless already changed)
            if ($slip->getResidence() === $this) {
                $slip->setResidence(null);
            }
        }

        return $this;
    }
}
