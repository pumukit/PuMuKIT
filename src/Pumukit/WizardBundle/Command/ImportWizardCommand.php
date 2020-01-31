<?php

namespace Pumukit\WizardBundle\Command;

use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class ImportWizardCommand.
 */
class ImportWizardCommand extends ContainerAwareCommand
{
    private $dm;
    private $jobService;
    private $profileService;
    private $inspectionService;
    private $wizardService;
    private $defaultLanguage;
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

    protected function configure()
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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->wizardService = $this->getContainer()->get('pumukit_wizard.wizard');
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->profileService = $this->getContainer()->get('pumukitencoder.profile');
        $this->inspectionService = $this->getContainer()->get('pumukit.inspection');
        $this->defaultLanguage = $this->getContainer()->getParameter('locale');
        $this->factoryService = $this->getContainer()->get('pumukitschema.factory');

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

    /**
     * @throws \Exception
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info> ***** Start - Generating Job for wizard tracks ***** </info>');

        $this->importFiles($output);

        $output->writeln('<info> ***** End - Generating Job for wizard tracks ***** </info>');
    }

    /**
     * @throws \Exception
     */
    private function importFiles(OutputInterface $output)
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
