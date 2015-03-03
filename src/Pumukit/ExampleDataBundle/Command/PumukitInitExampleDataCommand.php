<?php

namespace Pumukit\ExampleDataBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Pumukit\SchemaBundle\Document\Tag;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Link;

class PumukitInitExampleDataCommand extends ContainerAwareCommand
{
    private $dm = null;
    private $repo = null;

    private $tagsPath = "../Resources/data/tags/";

    protected function configure()
    {
        $this
            ->setName('pumukit:init:example')
            ->setDescription('Load Pumukit expample data fixtures to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

Command to load a data set of data into a database. Useful for init a demo Pumukit environment.

The --force parameter has to be used to actually drop the database.

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");

        if ($input->getOption('force')) {

            //$this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
	       
            $announce = true;
            $publicDate = new \DateTime("now");
            $title = 'Title';
            $subtitle = 'Subtitle';
            $description = 'Description';
            $header = 'Header';
            $footer = 'Footer';
            $copyright = 'Copyright';
            $keyword = 'Keyword';
            $line2 = 'Line2';
            $locale = 'en';

            $series = new Series();

            $series->setAnnounce($announce);
            $series->setPublicDate($publicDate);
            $series->setTitle($title);
            $series->setSubtitle($subtitle);
            $series->setDescription($description);
            $series->setHeader($header);
            $series->setFooter($footer);
            $series->setCopyright($copyright);
            $series->setKeyword($keyword);
            $series->setLine2($line2);
            $series->setLocale($locale);

            $titleEs = 'Título';
            $subtitleEs = 'Subtítulo';
            $descriptionEs = 'Descripción';
            $headerEs = 'Cabecera';
            $footerEs = 'Pie';
            $copyrightEs = 'Derechos de copia';
            $keywordEs = 'Palabra clave';
            $line2Es = 'Línea 2';
            $localeEs = 'es';

            $titleI18n = array($locale => $title, $localeEs => $titleEs);
            $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
            $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
            $headerI18n = array($locale => $header, $localeEs => $headerEs);
            $footerI18n = array($locale => $footer, $localeEs => $footerEs);
            $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
            $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
            $line2I18n = array($locale => $line2, $localeEs => $line2Es);

            $series->setI18nTitle($titleI18n);
            $series->setI18nSubtitle($subtitleI18n);
            $series->setI18nDescription($descriptionI18n);
            $series->setI18nHeader($headerI18n);
            $series->setI18nFooter($footerI18n);
            $series->setI18nCopyright($copyrightI18n);
            $series->setI18nKeyword($keywordI18n);
            $series->setI18nLine2($line2I18n);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($series);
            $dm->flush();

            $tags = array('tag_a', 'tag_b');
            $language = 'en';
            $url = '/bundles/pumukitexampledata/videos/74638.flv';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/videos/74638.flv');
            $mime = 'video/flv';
            $duration = 5000;
            $acodec = 'aac';
            $vcodec = 'mpeg4-HP';
            $bitrate = 10000;
            $framerate = 25;
            $only_audio = false;
            $channels = 1;
            $duration = 66666;
            $width = 1920;
            $height = 1080;
            $hide = false;
            $numview = 3;

            $track = new Track();
            $track->setTags($tags);
            $track->setLanguage($language);
            $track->setUrl($url);
            $track->setPath($path);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);
            $track->setBitrate($bitrate);
            $track->setFramerate($framerate);
            $track->setOnlyAudio($only_audio);
            $track->setChannels($channels);
            $track->setDuration($duration);
            $track->setWidth($width);
            $track->setHeight($height);
            $track->setHide($hide);
            $track->setNumview($numview);

            $tags = array('tag_a', 'tag_b');
            $url = '/bundles/pumukitexampledata/images/74638.jpg';
            $path = realpath(dirname(__FILE__) . '/../Resources/public/images/74638.jpg');
            $mime = 'image/jpg';

            $size = 3456;
            $width = 800;
            $height = 600;
            $hide = true; // Change assertTrue accordingly.

            $pic = new Pic();

            $pic->setTags($tags);
            $pic->setUrl($url);
            $pic->setPath($path);
            $pic->setMimeType($mime);
            $pic->setSize($size);
            $pic->setWidth($width);
            $pic->setHeight($height);
            $pic->setHide($hide);

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Introduction';
            $subtitle = 'Subtitle';
            $description = "Description";
            $numview = 2;

            $tag1 = new Tag();
            $tag1->setCod('tag1');
            $tag2 = new Tag();
            $tag2->setCod('tag2');
            $tag3 = new Tag();
            $tag3->setCod('tag3');
            $mm_tags = array($tag1, $tag2, $tag3);

            $multimediaObject = new MultimediaObject();
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($record_date);
            $multimediaObject->setPublicDate($public_date);
            $multimediaObject->setTitle($title);
            $multimediaObject->setSubtitle($subtitle);
            $multimediaObject->setDescription($description);
            $multimediaObject->addTag($tag1);
            $multimediaObject->addTag($tag2);
            $multimediaObject->addTag($tag3);
            $multimediaObject->setNumview($numview);

            $multimediaObject->addPic($pic);

            $multimediaObject->addTrack($track);

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $dm->persist($multimediaObject);
            $dm->flush();

        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would drop the database</info>');
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return -1;
        }
    }
}
