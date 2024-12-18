<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MoveFilesCommand extends Command
{
    private $dm;
    private $origin;
    private $destiny;
    private $limit;
    private $output;
    private $logger;

    public function __construct(DocumentManager $documentManager, LoggerInterface $logger)
    {
        $this->dm = $documentManager;
        $this->logger = $logger;
        parent::__construct();
    }

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
        $this->origin = $input->getOption('origin');
        $this->destiny = $input->getOption('destiny');
        $this->limit = $input->getOption('limit');
        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        return 0;
    }

    private function checkInputs(): void
    {
        if (!FileSystemUtils::exists($this->origin)) {
            throw new \Exception($this->origin." directory doesn't exists");
        }

        if (!FileSystemUtils::exists($this->destiny)) {
            throw new \Exception($this->destiny." directory doesn't exists");
        }

        if (!is_int($this->limit)) {
            throw new \Exception($this->limit." isn't integer");
        }
    }

    private function moveFiles(): void
    {
        $multimediaObjects = $this->getMultimediaObjects();

        $progress = new ProgressBar($this->output, is_countable($multimediaObjects) ? count($multimediaObjects) : 0);
        $progress->setFormat('verbose');
        $progress->start();

        $i = 0;
        foreach ($multimediaObjects as $multimediaObject) {
            if ($i > $this->limit) {
                break;
            }

            $track = $multimediaObject->getMaster();
            $path = $track->storage()->path()->path();
            if (!str_contains($path, (string) $this->origin)) {
                $this->logger->error('the root directory does not match on multimedia object '.$multimediaObject->getId());

                continue;
            }

            $progress->advance();

            if (!FileSystemUtils::exists($path)) {
                $this->logger->error('File not exists '.$multimediaObject->getId());

                continue;
            }

            $this->logger->info('Move file of multimedia object '.$multimediaObject->getId());

            $finalPath = str_replace($this->origin, $this->destiny, $path);

            $directory = pathinfo($finalPath);
            FileSystemUtils::createFolder($directory['dirname'].'/');
            FileSystemUtils::copy($path, $finalPath, true);
            FileSystemUtils::remove($path);

            $url = StorageUrl::create('');
            $path = Path::create($finalPath);
            $storage = Storage::create($url, $path);
            $track->updateStorage($storage);
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
