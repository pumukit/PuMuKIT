<?php

namespace Pumukit\EncoderBundle\Command;

use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitConvertPNGtoJPGpicsCommand extends ContainerAwareCommand
{
    private $dm;
    private $picExtractorService;
    private $multimediaObjectPicService;
    private $output;
    private $input;
    private $deletePngFiles;
    private $extensionFile = '.png';

    protected function configure()
    {
        $this
            ->setName('pumukit:regenerate:pics')
            ->setDescription('Pumukit regenerate pics png to jpg')
            ->addArgument('delete', InputArgument::OPTIONAL, 'Delete png files ( true or false')
            ->setHelp(
                <<<'EOT'
                ***** Command options *****

                php app/console pumukit:regenerate:pics false

                ** Use before command to not delete png pics

                php app/console pumukit:regenerate:pics true

                ** Use before command to delete pics

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->picExtractorService = $this->getContainer()->get('pumukitencoder.picextractor');
        $this->multimediaObjectPicService = $this->getContainer()->get('pumukitschema.mmspic');
        $this->output = $output;
        $this->input = $input;

        $this->deletePngFiles = $this->input->getArgument('delete');
    }

    /**
     * @throws \Exception
     *
     * @return bool|int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $criteria = [
            'pics' => ['$exists' => true],
            'pics.tags' => 'auto',
            'pics.path' => new Regex($this->extensionFile, 'i'),
        ];

        $multimediaObjects = $this->dm->getRepository(MultimediaObject::class)->findBy($criteria);

        if (!$multimediaObjects) {
            $output->writeln('No multimedia objects found to regenerate pics');

            return true;
        }

        try {
            $this->regeneratePicsOfMultimediaObjects($multimediaObjects);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param Multimediaobject[] $multimediaObjects
     *
     * @throws \Exception
     */
    private function regeneratePicsOfMultimediaObjects($multimediaObjects)
    {
        foreach ($multimediaObjects as $multimediaObject) {
            foreach ($multimediaObject->getPics() as $pic) {
                if (false !== stripos($pic->getPath(), '.png')) {
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

                    if ('true' == $this->deletePngFiles) {
                        $this->multimediaObjectPicService->removePicFromMultimediaObject($multimediaObject, $pic->getId());
                        $this->output->writeln('Deleted pic for the mmobj - '.$multimediaObject->getId().' with path '.$pic->getPath());
                    }
                }
            }
        }
    }
}
