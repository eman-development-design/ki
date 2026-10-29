<?php

namespace App\Command;

use App\Service\PageBuilderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'ki:build',
    description: 'This will compile the twig pages into HTML.',
)]
readonly class BuildCommand
{
    public function __construct(private LoggerInterface $logger, private PageBuilderService $pageBuilderService)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title('Site Generator');
        $io->section('Building Pages');

        // ensure pageParams.json file exists.
        if (!is_file('pageParams.json')) {
            $io->error('pageParams.json file was not found.');

            return Command::FAILURE;
        }

        try {
            $pageParams = json_decode(file_get_contents('pageParams.json'), true, 512, JSON_THROW_ON_ERROR);

            $this->pageBuilderService->buildPages($pageParams);

            $io->success('Pages Generated Successfully.');

            return Command::SUCCESS;
        } catch (Throwable $t) {
            $this->logger->error($t);

            $io->error($t->getMessage());

            return Command::FAILURE;
        }
    }
}
