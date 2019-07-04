<?php

namespace Pumukit\WizardBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Services\WizardService;
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
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var ProfileService
     */
    private $profileService;

    /**
     * @var InspectionServiceInterface
     */
    private $inspectionService;

    /**
     * @var WizardService
     */
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

    protected function configure()
    {
        $this
            ->setName('pumukit:wizard:import')
            ->setDescription('This command import generate job to import files from wizard')
            ->addArgument('user', InputArgument::REQUIRED, 'user')
            ->addArgument('path', InputArgument::REQUIRED, 'path')
            ->addArgument('inbox-depth', InputArgument::REQUIRED, 'inbox-depth')
            ->addArgument('series', null, InputArgument::REQUIRED, 'series')
            ->addArgument('status', null, InputArgument::REQUIRED, 'status')
            ->addArgument('channels', null, InputArgument::REQUIRED, 'channels')
            ->addArgument('profile', null, InputArgument::REQUIRED, 'profile')
            ->addArgument('priority', null, InputArgument::REQUIRED, 'priority')
            ->addArgument('language', null, InputArgument::REQUIRED, 'language')
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->wizardService = $this->getContainer()->get('pumukit_wizard.wizard');
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->profileService = $this->getContainer()->get('pumukitencoder.profile');
        $this->inspectionService = $this->getContainer()->get('pumukit.inspection');
        $this->defaultLanguage = $this->getContainer()->getParameter('locale');

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return null|int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info> ***** Start - Generating Job for wizard tracks ***** </info>');

        $this->importFiles($output);

        $output->writeln('<info> ***** End - Generating Job for wizard tracks ***** </info>');
    }

    /**
     * @param OutputInterface $output
     *
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
                        $this->wizardService->removeInvalidMultimediaObject($multimediaObject, $series);

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
