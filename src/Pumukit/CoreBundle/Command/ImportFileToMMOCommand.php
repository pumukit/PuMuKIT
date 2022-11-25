<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFileToMMOCommand extends Command
{
    private $documentManager;
    private $jobService;
    private $profileService;
    private $inspectionService;
    private $defaultLanguage;

    public function __construct(DocumentManager $documentManager, JobService $jobService, ProfileService $profileService, InspectionFfprobeService $inspectionService, string $locale)
    {
        $this->documentManager = $documentManager;
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->inspectionService = $inspectionService;
        $this->defaultLanguage = $locale;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:import:multimedia:file')
            ->setDescription('This command import file like a track on a multimedia object')
            ->addArgument('object', InputArgument::REQUIRED, 'object')
            ->addArgument('file', InputArgument::REQUIRED, 'file')
            ->addOption('profile', null, InputOption::VALUE_OPTIONAL, 'profile')
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'language', null)
            ->addArgument('description', InputArgument::OPTIONAL, 'description')
            ->setHelp(
                <<<'EOT'
This command import file like a track on a multimedia object

Example complete:
<info>php app/console pumukit:import:multimedia:file %idmultimediaobject% %pathfile% --profile=%profile% --language=%language% %description%</info>

Basic example:
<info>php app/console pumukit:import:multimedia:file 58a31ce08381165d008b456a {pathToPuMuKITStorageTempDir}/test.mp4</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info> ***** Add track to multimedia object ***** </info>');

        $filePath = $input->getArgument('file');
        if (is_string($filePath) && !is_file($filePath)) {
            throw new \Exception('Path is not a file: '.$filePath);
        }
        if (!is_string($filePath)) {
            throw new \Exception('Argument file must be an string');
        }

        try {
            $duration = $this->inspectionService->getDuration($filePath);
        } catch (\Exception $e) {
            throw new \Exception('The file is not a valid video or audio file');
        }

        if (0 == $duration) {
            throw new \Exception('The file is not a valid video or audio file (duration is zero)');
        }

        $multimediaObjectId = $input->getArgument('object');
        if (!is_string($multimediaObjectId)) {
            throw new \Exception('Error on object argument. This argument must be string');
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(
            ['id' => new ObjectId($multimediaObjectId)]
        );

        $profile = ($input->hasOption('profile')) ? $input->getOption('profile') : $this->profileService->getDefaultMasterProfile();
        $language = ($input->hasOption('language')) ? $input->getOption('language') : null;
        $description = ($input->hasArgument('description')) ? [$this->defaultLanguage => $input->getArgument('description')] : '';

        $track = $this->jobService->createTrack($multimediaObject, $filePath, $profile, $language, $description);
        $output->writeln('<info> Track '.$track->getId().' was imported succesfully on '.$multimediaObject->getId().'</info>');

        return 0;
    }
}
