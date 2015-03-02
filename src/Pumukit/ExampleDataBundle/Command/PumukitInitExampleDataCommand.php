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

            $rank = 3;
            $status = MultimediaObject::STATUS_NORMAL;
            $record_date = new \DateTime();
            $public_date = new \DateTime();
            $title = 'Star Wars';
            $subtitle = 'Spoiler';
            $description = "Darth Vader: Obi-Wan never told you what happened to your father.
                Luke Skywalker: He told me enough! He told me you killed him!
                Darth Vader: No. I am your father.
                Luke Skywalker: No... that's not true! That's impossible!";
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
