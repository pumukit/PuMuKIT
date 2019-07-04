<?php

namespace Pumukit\CoreBundle\Command;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFileToMMOCommand extends ContainerAwareCommand
{
    private $dm;
    private $mmobjRepo;
    private $jobService;
    private $profileService;
    private $inspectionService;
    private $defaultLanguage;

    protected function configure()
    {
        $this
            ->setName('import:multimedia:file')
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
<info>php app/console import:multimedia:file %idmultimediaobject% %pathfile% --profile=%profile% --language=%language% %description%</info>

Basic example:
<info>php app/console import:multimedia:file 58a31ce08381165d008b456a /var/www/html/pumukit2/web/storage/tmp/test.mp4</info>

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->jobService = $this->getContainer()->get('pumukitencoder.job');
        $this->profileService = $this->getContainer()->get('pumukitencoder.profile');
        $this->inspectionService = $this->getContainer()->get('pumukit.inspection');
        $this->defaultLanguage = $this->getContainer()->getParameter('locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info> ***** Add track to multimedia object ***** </info>');

        $filePath = $input->getArgument('file');
        if (!is_file($filePath)) {
            throw new \Exception('Path is not a file: '.$filePath);
        }

        try {
            $duration = $this->inspectionService->getDuration($filePath);
        } catch (\Exception $e) {
            throw new \Exception('The file is not a valid video or audio file');
        }

        if (0 == $duration) {
            throw new \Exception('The file is not a valid video or audio file (duration is zero)');
        }

        $multimediaObject = $this->mmobjRepo->findOneBy(
            ['id' => new \MongoId($input->getArgument('object'))]
        );

        $profile = ($input->hasOption('profile')) ? $input->getOption('profile') : $this->profileService->getDefaultMasterProfile();
        $language = ($input->hasOption('language')) ? $input->getOption('language') : null;
        $description = ($input->hasArgument('description')) ? [$this->defaultLanguage => $input->getArgument('description')] : '';

        $track = $this->jobService->createTrack($multimediaObject, $filePath, $profile, $language, $description);
        $output->writeln('<info> Track '.$track->getId().' was imported succesfully on '.$multimediaObject->getId().'</info>');
    }
}
