<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckOrphansConsistencyCommand extends Command
{
    private const MULTIMEDIA_OBJECT_PATH_FIELDS = [
        'tracks.storage.path',
        'documents.storage.path',
        'images.storage.path',
        'materials.path',
        'pics.path',
    ];

    private const SERIES_PATH_FIELDS = [
        'pics.path',
    ];

    private DocumentManager $documentManager;
    private ?string $path;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:check:orphans')
            ->setDescription('List files on a path that have no correspondence in the database')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to check', null)
            ->setHelp(
                <<<'EOT'
The <info>pumukit:check:orphans</info> command lists files under a path that are not
referenced anywhere in the database. A file is considered orphan when its path is not found in:

    a) tracks, documents or images storage of a multimedia object
    b) materials or pics of a multimedia object
    c) pics of a series

This command is read-only: it never deletes anything.

    Example:
        php bin/console pumukit:check:orphans --path="/var/www/html/pumukit/public/uploads/material"
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');
        if (!$path || !file_exists($path)) {
            $output->writeln('<error>Path does not exist: '.$path.'</error>');

            return Command::FAILURE;
        }

        $this->path = $path;

        $files = FinderUtils::filesFromPath($path);
        $output->writeln('<comment>***** Files to check: '.count($files).' *****</comment>');

        $orphans = 0;
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathName();
            if (!$this->existsInDatabase($relativePath)) {
                ++$orphans;
                $output->writeln('Orphan file: <info>'.$file->getPathName().'</info>');
            }
        }

        $output->writeln('<comment>***** Orphan files found: '.$orphans.' *****</comment>');

        return Command::SUCCESS;
    }

    private function existsInDatabase(string $filePath): bool
    {
        $regex = [
            '$regex' => preg_quote($filePath, '/'),
            '$options' => 'i',
        ];

        foreach (self::MULTIMEDIA_OBJECT_PATH_FIELDS as $field) {
            if (null !== $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([$field => $regex])) {
                return true;
            }
        }

        foreach (self::SERIES_PATH_FIELDS as $field) {
            if (null !== $this->documentManager->getRepository(Series::class)->findOneBy([$field => $regex])) {
                return true;
            }
        }

        return false;
    }
}
