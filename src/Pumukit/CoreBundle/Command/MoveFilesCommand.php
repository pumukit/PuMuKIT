<?php

namespace Pumukit\CoreBundle\Command;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class MoveFilesCommand extends ContainerAwareCommand
{
    private $dm;
    private $origin;
    private $destiny;
    private $limit;
    private $input;
    private $output;
    private $fileSystem;
    private $logger;

    protected function configure(): void
    {
        $this
            ->setName('pumukit:move:files')
            ->setDescription('This command move files from origin NAS to new NAS')
            ->addOption('origin', null, InputOption::VALUE_REQUIRED, 'Origin path NAS', '/mnt/nas/almacen/masters/')
            ->addOption('destiny', null, InputOption::VALUE_REQUIRED, 'Destiny path NAS', '/mnt/pumukit/storage/masters/')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit of files to move')
            ->setHelp(
                <<<'EOT'

                Use the command like this:

                php app/console pumukit:move:files --origin=/mnt/nas/almacen/masters --destiny=/mnt/newnas/newalmacen/masters --limit=1

                to copy master files of multimedia objects to destiny path ( keeping personal path of file )
EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->origin = $input->getOption('origin');
        $this->destiny = $input->getOption('destiny');
        $this->limit = $input->getOption('limit');
        $this->limit = abs(intval($this->limit));

        $this->logger = $this->getContainer()->get('logger');

        $this->fileSystem = new Filesystem();

        $this->input = $input;
        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->checkInputs();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        try {
            $this->moveFiles();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $this->output->writeln('Move files done');
    }

    private function checkInputs(): void
    {
        if (!$this->fileSystem->exists($this->origin)) {
            throw new \Exception($this->origin." directory doesn't exists");
        }

        if (!$this->fileSystem->exists($this->destiny)) {
            throw new \Exception($this->destiny." directory doesn't exists");
        }

        if (!is_int($this->limit)) {
            throw new \Exception($this->limit." isn't integer");
        }
    }

    private function moveFiles(): void
    {
        $multimediaObjects = $this->getMultimediaObjects();

        $progress = new ProgressBar($this->output, count($multimediaObjects));
        $progress->setFormat('verbose');
        $progress->start();

        $i = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            if ($i > $this->limit) {
                break;
            }

            $track = $multimediaObject->getMaster();
            if (false === strpos($track->getPath(), $this->origin)) {
                $this->logger->error('the root directory does not match on multimedia object '.$multimediaObject->getId());

                continue;
            }

            $progress->advance();

            if (!$this->fileSystem->exists($track->getPath())) {
                $this->logger->error('File not exists '.$multimediaObject->getId());

                continue;
            }

            $this->logger->info('Move file of multimedia object '.$multimediaObject->getId());

            $finalPath = str_replace($this->origin, $this->destiny, $track->getPath());

            $directory = pathinfo($finalPath);
            $this->fileSystem->mkdir($directory['dirname'].'/', 0775);
            $this->fileSystem->copy($track->getPath(), $finalPath, true);

            $this->fileSystem->remove($track->getPath());

            $track->setPath($finalPath);
            $track->setProperty('moved', true);

            ++$i;
            $this->dm->flush();
        }

        $this->dm->flush();
        $this->dm->clear();

        $progress->finish();
    }

    private function getMultimediaObjects()
    {
        return $this->dm->getRepository(MultimediaObject::class)->findBy(
            [
                'tracks.tags' => 'master',
                'tracks.properties.moved' => ['$exists' => false],
            ],
            ['_id' => 1],
            $this->limit
        );
    }
}
