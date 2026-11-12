<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:personal-series:move', description: 'Move the multimedia objects of a series to the personal series of their owner')]
final class PersonalSeriesMoveCommand extends Command
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly PersonalSeriesService $personalSeriesService,
        private readonly MultimediaObjectEventDispatcherService $multimediaObjectDispatcher,
        private readonly SortedMultimediaObjectsService $sortedMultimediaObjectsService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('series', InputArgument::REQUIRED, 'Id of the series to empty')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be moved without changing anything')
            ->setHelp(
                <<<'EOT'
Moves every multimedia object of the series to the personal series of its owner, creating that
personal series when the owner does not have one yet. Objects with no owner, with two or more
owners, or whose owner has no user are listed and left in the series.

<info>php bin/console pumukit:personal-series:move 5f1b2c3d4e5f6a7b8c9d0e1f --dry-run</info>
<info>php bin/console pumukit:personal-series:move 5f1b2c3d4e5f6a7b8c9d0e1f</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $seriesId = (string) $input->getArgument('series');
        $series = preg_match('/^[a-f\d]{24}$/i', $seriesId)
            ? $this->documentManager->getRepository(Series::class)->find(new ObjectId($seriesId))
            : null;
        if (!$series instanceof Series) {
            $io->error(sprintf('Series "%s" not found.', $seriesId));

            return Command::FAILURE;
        }

        $moved = [];
        $skipped = [];
        $targets = [];
        $multimediaObjects = iterator_to_array($this->documentManager->getRepository(MultimediaObject::class)->findWithoutPrototype($series), false);
        foreach ($multimediaObjects as $multimediaObject) {
            $owners = $this->personalSeriesService->owners($multimediaObject);
            if (1 !== count($owners)) {
                $skipped[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), $this->ownersReason($owners)];

                continue;
            }

            $owner = $owners[0];
            $user = $this->personalSeriesService->userOf((string) $owner->getId());
            if (null === $user) {
                $skipped[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), sprintf('Owner "%s" has no user', $owner->getName())];

                continue;
            }

            if ($dryRun) {
                $target = $this->personalSeriesService->find($user);
                if ($target instanceof Series && $target->getId() === $series->getId()) {
                    $skipped[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), 'Already in the personal series of its owner'];

                    continue;
                }
                $moved[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), $user->getUsername(), $target?->getId() ?? 'new'];

                continue;
            }

            $target = $this->personalSeriesService->getOrCreate($user);
            if ($target->getId() === $series->getId()) {
                $skipped[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), 'Already in the personal series of its owner'];

                continue;
            }

            $multimediaObject->setSeries($target);
            if (Series::SORT_MANUAL === $target->getSorting()) {
                $multimediaObject->setRank(-1);
            }
            $this->documentManager->flush();
            $this->multimediaObjectDispatcher->dispatchUpdate($multimediaObject);
            $targets[$target->getId()] = $target;
            $moved[] = [$multimediaObject->getId(), $multimediaObject->getTitle(), $user->getUsername(), $target->getId()];
        }

        foreach ($targets as $target) {
            $this->sortedMultimediaObjectsService->reorder($target);
        }
        if ([] !== $targets) {
            $this->sortedMultimediaObjectsService->reorder($series);
        }

        if ([] !== $moved) {
            $io->section($dryRun ? 'Would be moved' : 'Moved');
            $io->table(['Id', 'Title', 'Owner', 'Personal series'], $moved);
        }
        if ([] !== $skipped) {
            $io->section('Not moved');
            $io->table(['Id', 'Title', 'Reason'], $skipped);
        }

        $io->success(sprintf(
            '%d multimedia objects %s, %d not moved.',
            count($moved),
            $dryRun ? 'would be moved' : 'moved',
            count($skipped)
        ));

        return Command::SUCCESS;
    }

    private function ownersReason(array $owners): string
    {
        if ([] === $owners) {
            return 'No owner';
        }

        return sprintf('%d owners: %s', count($owners), implode(', ', array_map(static fn ($owner): string => (string) $owner->getName(), $owners)));
    }
}
