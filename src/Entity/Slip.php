<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SlipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SlipRepository::class)]
class Slip
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private ?string $id = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'slips')]
    private ?Resident $residence = null;

    public function __construct(
        string $id,
        int $amount,
        \DateTimeInterface $dueDate
    )
    {
        $this->id = $id;
        $this->amount = $amount;
        if (!$dueDate instanceof \DateTimeImmutable) {
            $this->dueDate = \DateTimeImmutable::createFromInterface($dueDate);
        } else {
            $this->dueDate = $dueDate;
        }
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $id,
        int $amount,
        \DateTimeInterface $dueDate
    ): self
    {

        return new self($id, $amount, $dueDate);
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function amount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function dueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function residence(): ?Resident
    {
        return $this->residence;
    }

    public function setResidence(?Resident $residence): static
    {
        $this->residence = $residence;

        return $this;
    }
}
