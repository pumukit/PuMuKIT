<?php

namespace Pumukit\WizardBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\WizardBundle\Services\WizardService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ImportWizardCommand extends Command
{
    private $dm;
    private $jobService;
    private $inspectionService;
    private $wizardService;
    private $user;
    private $path;
    private $inboxDepth;
    private $series;
    private $status;
    private $channels;
    private $profile;
    private $priority;
    private $language;
    private $factoryService;

    public function __construct(DocumentManager $documentManager, WizardService $wizardService, JobService $jobService, InspectionFfprobeService $inspectionFfprobeService, FactoryService $factoryService)
    {
        $this->dm = $documentManager;
        $this->wizardService = $wizardService;
        $this->jobService = $jobService;
        $this->inspectionService = $inspectionFfprobeService;
        $this->factoryService = $factoryService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:wizard:import')
            ->setDescription('This command import generate job to import files from wizard')
            ->addArgument('user', InputArgument::REQUIRED, 'user')
            ->addArgument('path', InputArgument::REQUIRED, 'path')
            ->addArgument('inbox-depth', InputArgument::REQUIRED, 'inbox-depth')
            ->addArgument('series', InputArgument::REQUIRED, 'series')
            ->addArgument('status', InputArgument::REQUIRED, 'status')
            ->addArgument('channels', InputArgument::REQUIRED, 'channels')
            ->addArgument('profile', InputArgument::REQUIRED, 'profile')
            ->addArgument('priority', InputArgument::REQUIRED, 'priority')
            ->addArgument('language', InputArgument::REQUIRED, 'language')
            ->setHelp(
                <<<'EOT'
This command import generate job to import files from wizard

Example:
<info>
php app/console pumukit:wizard:import %user% %path% %inbox-depth% %series% %status %channels% %profile% %priority% %language%
</info>

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->user = $this->dm->getRepository(User::class)->findOneBy([
            '_id' => $input->getArgument('user'),
        ]);

        $this->path = $input->getArgument('path');
        $this->inboxDepth = $input->getArgument('inbox-depth');
        $this->series = $input->getArgument('series');
        $this->status = $input->getArgument('status');
        $this->channels = $input->getArgument('channels');
        $this->profile = $input->getArgument('profile');
        $this->priority = $input->getArgument('priority');
        $this->language = $input->getArgument('language');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('<info> ***** Start - Generating Job for wizard tracks ***** </info>');

        $this->importFiles($output);

        $output->writeln('<info> ***** End - Generating Job for wizard tracks ***** </info>');
    }

    private function importFiles(OutputInterface $output): void
    {
        $series = $this->dm->getRepository(Series::class)->findOneBy(['_id' => $this->series]);
        if (!$series) {
            throw new \Exception(__FUNCTION__.' - Series not found'.$this->series);
        }

        $finder = new Finder();
        if (0 === $this->inboxDepth) {
            $finder->depth('== 0');
        }

        if (!realpath($this->path)) {
            throw new \Exception(__FUNCTION__.' - Invalid path '.$this->path);
        }

        $finder->files()->in($this->path);

        foreach ($finder as $file) {
            $filePath = $file->getRealpath();

            try {
                $this->inspectionService->getDuration($filePath);
            } catch (\Exception $e) {
                continue;
            }

            $titleData = $this->wizardService->getDefaultFieldValuesInData([], 'i18n_title', $file->getRelativePathname(), true);

            $multimediaObject = $this->wizardService->createMultimediaObject($titleData, $series, $this->user);
            $output->writeln('Video '.$multimediaObject->getId().' importing file '.$filePath);

            if ($multimediaObject) {
                try {
                    $multimediaObject = $this->jobService->createTrackFromInboxOnServer(
                        $multimediaObject,
                        $filePath,
                        $this->profile,
                        $this->priority,
                        $this->language,
                        [],
                        [],
                        0,
                        JobService::ADD_JOB_UNIQUE
                    );
                } catch (\Exception $e) {
                    if (!strpos($e->getMessage(), 'Unknown error')) {
                        $this->factoryService->deleteMultimediaObject($multimediaObject);

                        throw $e;
                    }
                }
                $pubChannels = explode(',', $this->channels);
                foreach ($pubChannels as $code) {
                    $this->wizardService->addTagToMultimediaObjectByCode($multimediaObject, $code, $this->user);
                }

                if ($multimediaObject && isset($this->status)) {
                    $multimediaObject->setStatus((int) ($this->status));
                }
                $this->dm->flush();
            }
        }
    }
}
