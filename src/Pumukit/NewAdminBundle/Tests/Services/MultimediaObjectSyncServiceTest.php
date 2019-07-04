<?php

namespace Pumukit\NewAdminBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectSyncServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $announceService;
    private $factoryService;
    private $tagService;
    private $syncService;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()->get('doctrine_mongodb')->getManager();

        $this->mmobjRepo = $this->dm->getRepository(MultimediaObject::class);

        $this->syncService = static::$kernel->getContainer()->get('pumukitnewadmin.multimedia_object_sync');
        $this->tagService = static::$kernel->getContainer()->get('pumukitschema.tag');
        $this->factoryService = static::$kernel->getContainer()->get('pumukitschema.factory');

        $this->dm->getDocumentCollection(MultimediaObject::class)->remove([]);
        $this->dm->getDocumentCollection(Role::class)->remove([]);
        $this->dm->getDocumentCollection(Tag::class)->remove([]);
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->mmobjRepo = null;
        $this->announceService = null;
        $this->factoryService = null;
        $this->tagService = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testSyncMetadata()
    {
        $date = new \DateTime();
        $series = $this->factoryService->createSeries();

        $this->dm->persist($series);
        $this->dm->flush();

        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setComments('Comments');
        $multimediaObject->setCopyright('Copyright');
        $i18nDescription = ['es' => 'descripción', 'en' => 'description'];
        $multimediaObject->setI18nDescription($i18nDescription);
        $i18nLine2 = ['es' => 'line 2 es', 'en' => 'line 2 en'];
        $multimediaObject->setI18nLine2($i18nLine2);
        $i18nKeywords = ['es' => ['a', 'b'], 'en' => ['c', 'd']];
        $multimediaObject->setI18nKeywords($i18nKeywords);
        $multimediaObject->setLicense('Licencia');
        $multimediaObject->setPublicDate($date);
        $multimediaObject->setRecordDate($date);
        $i18nSubseries = ['es' => 'subserie', 'en' => 'subseries'];
        $multimediaObject->setProperty('subseriestitle', $i18nSubseries);
        $multimediaObject->setProperty('subseries', true);

        // Add group
        $group = new Group();
        $group->setKey('key');
        $group->setName('name');
        $this->dm->persist($group);
        $multimediaObject->addGroup($group);

        // Add person with role
        $person = new Person();
        $person->setName('person test');
        $person->setEmail('person@mail.com');
        $this->dm->persist($person);

        $role = new Role();
        $role->setCod('owner');
        $role->setRank(1);
        $role->setXml('owner');
        $role->setDisplay(true);
        $role->setName('owner');
        $this->dm->persist($role);

        $roleAuthor = new Role();
        $roleAuthor->setCod('author');
        $roleAuthor->setRank(1);
        $roleAuthor->setXml('author');
        $roleAuthor->setDisplay(true);
        $roleAuthor->setName('author');
        $this->dm->persist($roleAuthor);

        $multimediaObject->addPersonWithRole($person, $role);
        $multimediaObject->addPersonWithRole($person, $roleAuthor);

        // Add publishing decision
        $tag = new Tag();
        $tag->setCod('PUBDECISIONS');
        $tag->setMetatag(true);
        $tag->setDisplay(true);
        $tag->setTitle('title', 'es');
        $this->dm->persist($tag);

        $tag2 = new Tag();
        $tag2->setCod('other_pub_decision');
        $tag2->setMetatag(false);
        $tag2->setDisplay(true);
        $tag2->setTitle('title', 'es');
        $tag2->setParent($tag);
        $this->dm->persist($tag2);

        // Add unesco tag
        $tagUNESCO = new Tag();
        $tagUNESCO->setCod('UNESCO');
        $tagUNESCO->setMetatag(true);
        $tagUNESCO->setDisplay(true);
        $tagUNESCO->setTitle('UNESCO', 'es');
        $this->dm->persist($tagUNESCO);

        $tag3 = new Tag();
        $tag3->setCod('science');
        $tag3->setMetatag(false);
        $tag3->setDisplay(true);
        $tag3->setTitle('Science', 'es');
        $tag3->setParent($tagUNESCO);
        $this->dm->persist($tag3);

        $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        $this->tagService->addTagToMultimediaObject($multimediaObject, $tag2->getId());
        $this->tagService->addTagToMultimediaObject($multimediaObject, $tagUNESCO->getId());
        $this->tagService->addTagToMultimediaObject($multimediaObject, $tag3->getId());

        $this->dm->persist($multimediaObject);

        $multimediaObject2 = $this->factoryService->createMultimediaObject($series);

        $this->dm->persist($multimediaObject2);
        $this->dm->flush();

        $syncFieldTag = 'metadata_tag_'.$tag3->getId();
        $syncFieldRole = 'metadata_role_'.$roleAuthor->getId();
        $syncFields = [
            'metadata_comments_all' => 'on',
            'metadata_copyright_all' => 'on',
            'metadata_description_all' => 'on',
            'metadata_groups_all' => 'on',
            'metadata_headline_all' => 'on',
            'metadata_keywords_all' => 'on',
            'metadata_license_all' => 'on',
            'metadata_owners_all' => 'on',
            'metadata_publicdate_all' => 'on',
            'metadata_publishingdecisions_all' => 'on',
            'metadata_recorddate_all' => 'on',
            'metadata_subseries_all' => 'on',
            $syncFieldTag => 'on',
            $syncFieldRole => 'on',
        ];

        $this->syncService->syncMetadata([$multimediaObject2], $multimediaObject, $syncFields);

        $this->assertEquals($multimediaObject->getComments(), $multimediaObject2->getComments());
        $this->assertEquals($multimediaObject->getCopyright(), $multimediaObject2->getCopyright());
        $this->assertEquals($multimediaObject->getI18nDescription(), $multimediaObject2->getI18nDescription());
        $this->assertEquals($multimediaObject->getI18nLine2(), $multimediaObject2->getI18nLine2());
        $this->assertEquals($multimediaObject->getI18nKeywords(), $multimediaObject2->getI18nKeywords());
        $this->assertEquals($multimediaObject->getLicense(), $multimediaObject2->getLicense());
        $this->assertEquals($multimediaObject->getEmbeddedRole($role), $multimediaObject2->getEmbeddedRole($role));
        $this->assertEquals($multimediaObject->getPublicDate(), $multimediaObject2->getPublicDate());
        $this->assertEquals($multimediaObject->getRecordDate(), $multimediaObject2->getRecordDate());
        $this->assertEquals($multimediaObject->containsTag($tag2), $multimediaObject2->containsTag($tag2));
        $this->assertEquals($multimediaObject->getProperty('subseriestitle'), $multimediaObject2->getProperty('subseriestitle'));
        $this->assertEquals($multimediaObject->getProperty('subseries'), $multimediaObject2->getProperty('subseries'));
        $this->assertEquals($multimediaObject->getEmbeddedRole($roleAuthor), $multimediaObject2->getEmbeddedRole($roleAuthor));
        $this->assertEquals($multimediaObject->containsTag($tag3), $multimediaObject2->containsTag($tag3));
    }
}
