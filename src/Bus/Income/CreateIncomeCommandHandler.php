<?php

declare(strict_types=1);

namespace App\Bus\Income;

use App\Entity\Income;
use App\Event\Income\IncomeWasCreated;
use App\Repository\IncomeRepository;
use App\Repository\IncomeTypeRepository;
use App\Repository\ResidentRepository;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class CreateIncomeCommandHandler
{
    public function __construct(
        private IncomeTypeRepository $incomeTypeRepository,
        private ResidentRepository $residentRepository,
        private IncomeRepository $incomeRepository,
        private MessageBusInterface $bus
    ) {}

    /**
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function __invoke(CreateIncomeCommand $command): void
    {
        $incomeType = $this->incomeTypeRepository->findOneByIdOrFail($command->incomeTypeId);
        $resident = $this->residentRepository->findOneByIdOrFail($command->residentId);

        $income = Income::create(
            Uuid::v4()->toRfc4122(),
            $command->amount,
            $incomeType,
            $resident,
            new \DateTime($command->dueDate)
        );

        if ($command->description) {
            $income->addDescription($command->description);
        }

        $this->incomeRepository->save($income, true);

        $event = new IncomeWasCreated(
            $income->id(),
            $income->amount(),
            $income->description(),
            $income->createdAt()::ATOM,
            (string)$income->type()->id(),
            $income->residence()->id(),
            Uuid::v4()->toRfc4122(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );

        $this->bus->dispatch($event);
    }
}
