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

class DeleteOrphanFilesCommand extends Command
{
    private $documentManager;
    private $input;
    private $path;
    private $delete;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:files:delete:orphan')
            ->setDescription('Pumukit delete orphan files on folders')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to check', null)
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete files and folders')
            ->setHelp(
                <<<'EOT'

            Pumukit delete orphan files on specific path. This command shows if the path's file exists on:

            a) pics.path of multimedia object
            b) materials.path of multimedia object
            c) tracks.path of multimedia object
            d) pics.path of series
            e) materials.path of series

            The command will try to delete the folder if its empty.

                Example to use:

                1. List orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit/web/uploads/material"
                2. Delete orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit/web/uploads/material" --delete

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $this->path = $this->input->getOption('path');
        $this->delete = $this->input->getOption('delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists($this->path)) {
            throw new \Exception('Path doesnt exists');
        }

        $this->findFilesOfPath($output, $this->path);

        return 0;
    }

    private function findFilesOfPath(OutputInterface $output, string $path)
    {
        $files = FinderUtils::filesFromPath($path);

        $output->writeln('<comment>***** Files to check: '.count($files).' *****</comment>');
        foreach ($files as $file) {
            $filePath = $file->getRelativePathName();
            $absoluteFilePath = $file->getPathName();

            $existsInMongoDB = $this->findInMongoDB($filePath);
            if (!$existsInMongoDB) {
                $output->writeln('No document found in MongoDB: <info>'.$this->path.'/'.$filePath.'</info>');

                if ($this->delete) {
                    $output->writeln('Trying to delete file....');
                    unlink($absoluteFilePath);
                    $output->writeln('File deleted '.$filePath);
                    $this->isEmptyDirectory($output, $absoluteFilePath);
                }
            }
        }
    }

    private function findInMongoDB(string $filePath): bool
    {
        $mmobjPic = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'pics.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);
        $mmobjMaterial = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'materials.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        $mmobjTracks = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'tracks.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        $seriesPic = $this->documentManager->getRepository(Series::class)->findOneBy([
            'pic.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);
        $seriesMaterial = $this->documentManager->getRepository(Series::class)->findOneBy([
            'materials.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        return !(!$mmobjPic && !$mmobjMaterial && !$mmobjTracks && !$seriesPic && !$seriesMaterial);
    }

    /**
     * @param string $directoryPath
     */
    private function isEmptyDirectory(OutputInterface $output, $directoryPath)
    {
        $dirName = pathinfo($directoryPath, PATHINFO_DIRNAME);

        try {
            $path = realpath($dirName);
            if ($path) {
                if (rmdir($path)) {
                    $output->writeln('Deleted empty directory '.$directoryPath);
                }
            }
        } catch (\Exception $exception) {
            $output->writeln('Cannot delete directory because is not empty '.$directoryPath);
        }
    }
}
