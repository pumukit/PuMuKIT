<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\EncoderBundle\Services\PicExtractorService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitConvertPNGtoJPGpicsCommand extends Command
{
    private $dm;
    private $picExtractorService;
    private $multimediaObjectPicService;
    private $output;
    private $input;
    private $deletePngFiles;
    private $extensionFile = '.png';

    public function __construct(DocumentManager $documentManager, PicExtractorService $picExtractorService, MultimediaObjectPicService $multimediaObjectPicService)
    {
        $this->dm = $documentManager;
        $this->picExtractorService = $picExtractorService;
        $this->multimediaObjectPicService = $multimediaObjectPicService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:regenerate:pics')
            ->setDescription('Regenerate auto pics from PNG to JPG re-extracting from master')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete the original PNG pic after regenerating')
            ->setHelp(
                <<<'EOT'
                Regenerate auto PNG pics by re-extracting from the master track.

                Without --delete, the regenerated JPG is added and the original PNG is kept.
                With --delete, the original PNG pic is removed from the multimedia object.

                    php bin/console pumukit:regenerate:pics
                    php bin/console pumukit:regenerate:pics --delete
EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->input = $input;

        $this->deletePngFiles = (bool) $this->input->getOption('delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $criteria = [
            'pics' => ['$exists' => true],
            'pics.tags' => 'auto',
            'pics.path' => new Regex($this->extensionFile, 'i'),
        ];

        $multimediaObjects = $this->dm->getRepository(MultimediaObject::class)->findBy($criteria);

        if (!$multimediaObjects) {
            $output->writeln('No multimedia objects found to regenerate pics');

            return Command::SUCCESS;
        }

        $this->regeneratePicsOfMultimediaObjects($multimediaObjects);

        return Command::SUCCESS;
    }

    private function regeneratePicsOfMultimediaObjects($multimediaObjects): void
    {
        foreach ($multimediaObjects as $multimediaObject) {
            $this->regeneratePicOnMultimediaObject($multimediaObject);
        }
    }

    private function regeneratePicOnMultimediaObject(MultimediaObject $multimediaObject): void
    {
        foreach ($multimediaObject->getPics() as $pic) {
            $path = $pic->getPath();
            if (null === $path || false === stripos($path, '.png')) {
                continue;
            }

            $picTags = $pic->getTags();

            if (in_array('auto', $picTags)) {
                foreach ($picTags as $tag) {
                    if (false !== strpos($tag, 'frame_')) {
                        $frame = explode('frame_', $tag);
                        if ($multimediaObject->getMaster()) {
                            $this->picExtractorService->extractPic(
                                $multimediaObject,
                                $multimediaObject->getMaster(),
                                $frame[1]
                            );
                        }
                        $this->output->writeln('Created new pic for the mmobj - '.$multimediaObject->getId());
                    }
                }
            }

            if ($this->deletePngFiles) {
                $this->multimediaObjectPicService->removePicFromMultimediaObject($multimediaObject, $pic->getId());
                $this->output->writeln('Deleted pic for the mmobj - '.$multimediaObject->getId().' with path '.$path);
            }
        }
    }
}
