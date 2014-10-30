<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Pumukit\SchemaBundle\Command;

use Assetic\Util\VarUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;


use Pumukit\SchemaBundle\Document\Tag;

/**
 * Dumps assets as their source files are modified.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class PumukitTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:test')
            ->setDescription('Pumukit test command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // capture error output
        $stderr = $stdout instanceof ConsoleOutputInterface
            ? $stdout->getErrorOutput()
            : $stdout;


	$dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
	$repo = $this->getContainer()->get('doctrine_mongodb')->getRepository("PumukitSchemaBundle:Tag");
	
	
	$tag = new Tag();
	$tag->setTitle("1");
	$tag->setPathSource("caca 1");
	var_dump($dm->persist($tag));
	var_dump($dm->flush());

	echo $tag->getPath();

	

	$tag2 = new Tag();
	$tag2->setTitle("2");
	$tag2->setPathSource("caca 2");
	$tag2->setParent($tag);
	var_dump($dm->persist($tag2));
	var_dump($dm->flush());

	echo $tag2->getPath();


	echo "\n---------------------------------\n";
	

	
    }
}
