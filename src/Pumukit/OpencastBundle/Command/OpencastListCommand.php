<?php

namespace Pumukit\OpencastBundle\Command;

use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpencastListCommand.
 */
class OpencastListCommand extends ContainerAwareCommand
{
    /**
     * @var ClientService
     */
    private $clientService;

    private $dm;

    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:list')
            ->setDescription('List imported or not mediapackages on PuMuKIT')
            ->setHelp(
                <<<'EOT'
            
            Show not imported mediaPackages on PuMuKIT
            
            Example:
            php app/console pumukit:opencast:list
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
        $this->clientService = $this->getContainer()->get('pumukit_opencast.client');
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
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
        [$total, $mediaPackages] = $this->clientService->getMediaPackages([], 0, 0);

        $output->writeln('Total - '.$total);

        foreach ($mediaPackages as $mediaPackage) {
            $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->findOneBy([
                'properties.opencast' => $mediaPackage['id'],
            ]);

            if (!$multimediaObject) {
                $output->writeln('MediaPackage - <info>'.$mediaPackage['id'].'</info>');
            }
        }
    }
}
