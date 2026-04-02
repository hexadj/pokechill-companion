<?php

declare(strict_types=1);

namespace App\Command;

use App\ReferenceData\Seeder\TypeEffectivenessSeeder;
use App\ReferenceData\Seeder\TypeSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:reference-data:seed', description: 'Seed local reference data (types, type effectiveness matrix).')]
final class ReferenceDataSeedCommand extends Command
{
    public function __construct(
        private readonly TypeSeeder $typeSeeder,
        private readonly TypeEffectivenessSeeder $typeEffectivenessSeeder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Reference data seeding');

        $typeResult = $this->typeSeeder->seed();
        $io->writeln(sprintf(
            'Types: created=%d updated=%d total=%d',
            $typeResult['created'],
            $typeResult['updated'],
            $typeResult['total'],
        ));

        $matrixResult = $this->typeEffectivenessSeeder->seed();
        $io->writeln(sprintf('Type effectiveness: inserted=%d', $matrixResult['inserted']));

        $io->success('Reference data seeded successfully.');

        return Command::SUCCESS;
    }
}

