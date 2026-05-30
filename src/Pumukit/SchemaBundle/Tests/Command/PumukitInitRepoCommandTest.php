<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class PumukitInitRepoCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testWithoutForceKeepsExistingTags(): void
    {
        $preExistingTag = $this->seedSurvivorTag();

        $tester = $this->commandTester();
        $tester->execute(['repo' => 'tag']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->dm->clear();
        $reloaded = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'TEST_SURVIVOR']);
        $this->assertNotNull(
            $reloaded,
            'Without --force the existing tags must NOT be dropped',
        );
        $this->assertSame($preExistingTag, (string) $reloaded->getId());
    }

    public function testWithForceDropsCollectionAndRecreatesRoot(): void
    {
        $preExistingTag = $this->seedSurvivorTag();

        $tester = $this->commandTester();
        $tester->execute(['repo' => 'tag', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->dm->clear();
        $survivor = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'TEST_SURVIVOR']);
        $this->assertNull($survivor, '--force must drop existing tags');

        $root = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'ROOT']);
        $this->assertNotNull($root, '--force must recreate the ROOT tag');
    }

    private function seedSurvivorTag(): string
    {
        $tag = new Tag();
        $tag->setCod('TEST_SURVIVOR');
        $tag->setTitle('survivor', 'en');
        $this->dm->persist($tag);
        $this->dm->flush();

        return (string) $tag->getId();
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:init:repo');

        return new CommandTester($command);
    }
}
