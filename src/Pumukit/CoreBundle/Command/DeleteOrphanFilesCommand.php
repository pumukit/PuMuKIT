<?php

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

/**
 * Class DeleteOrphanFilesCommand.
 */
class DeleteOrphanFilesCommand extends ContainerAwareCommand
{
    private $dm;
    private $output;
    private $input;
    private $path;
    private $delete;
    private $logger;

    protected function configure()
    {
        $this
            ->setName('pumukit:files:delete:orphan')
            ->setDescription('Pumukit delete orphan files on folders')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to check', null)
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete files and folders')
            ->setHelp(<<<'EOT'

            Pumukit delete orphan files on specific path. This command shows if the path's file exists on:

            a) pics.path of multimedia object
            b) materials.path of multimedia object
            c) tracks.path of multimedia object
            d) pics.path of series
            e) materials.path of series

            The command will try to delete the folder if its empty.

                Example to use:

                1. List orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit2/web/uploads/material"
                2. Delete orphan files
                    php app/console pumukit:files:delete:orphan --path="/var/www/html/pumukit2/web/uploads/material" --delete

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->output = $output;
        $this->input = $input;

        $this->path = $this->input->getOption('path');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!file_exists($this->path)) {
            throw new \Exception('Path doesnt exists');
        }

        $this->findFilesOfPath($output, $this->dm, $this->path);
    }

    /**
     * @param OutputInterface $output
     * @param DocumentManager $documentManager
     * @param string          $path
     */
    private function findFilesOfPath(OutputInterface $output, DocumentManager $documentManager, $path)
    {
        $finder = new Finder();
        $files = $finder->files()->in($path);

        $output->writeln('<comment>***** Files to delete: '.count($files).' *****</comment>');
        foreach ($files as $file) {
            $filePath = $file->getRelativePathName();
            $absoluteFilePath = $file->getPathName();

            $existsInMongoDB = $this->findInMongoDB($documentManager, $filePath);
            if (!$existsInMongoDB) {
                $output->writeln('No file found in MongoDB - <info>'.$this->path.'/'.$filePath.'</info>');

                if ($this->delete) {
                    $output->writeln('Trying to delete file....');
                    unlink($absoluteFilePath);
                    $output->writeln('File deleted '.$filePath);
                    $this->isEmptyDirectory($output, $absoluteFilePath);
                }
            }
        }
    }

    /**
     * @param DocumentManager $documentManager
     * @param string          $filePath
     *
     * @return bool
     */
    private function findInMongoDB(DocumentManager $documentManager, $filePath)
    {
        $mmobjPic = $documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'pics.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);
        $mmobjMaterial = $documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'materials.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        $mmobjTracks = $documentManager->getRepository(MultimediaObject::class)->findOneBy([
            'tracks.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        $seriesPic = $documentManager->getRepository(Series::class)->findOneBy([
            'pic.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);
        $seriesMaterial = $documentManager->getRepository(Series::class)->findOneBy([
            'materials.path' => [
                '$regex' => $filePath,
                '$options' => 'i',
            ],
        ]);

        if (!$mmobjPic && !$mmobjMaterial && !$mmobjTracks && !$seriesPic && !$seriesMaterial) {
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string          $directoryPath
     */
    private function isEmptyDirectory(OutputInterface $output, $directoryPath)
    {
        $dirName = pathinfo($directoryPath, PATHINFO_DIRNAME);
        try {
            if (realpath($dirName)) {
                if (rmdir(realpath($dirName))) {
                    $output->writeln('Deleted empty directory '.$directoryPath);
                }
            }
        } catch (\Exception $exception) {
            $output->writeln('Cannot delete directory because is not empty '.$directoryPath);
        }
    }
}
