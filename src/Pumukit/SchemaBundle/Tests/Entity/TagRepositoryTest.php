<?php

namespace Pumukit\SchemaBundle\Tests\Entity;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Entity\Tag;

class TagRepositoryTest extends WebTestCase
{
    private $em;
    private $repo;
    private $trepo;

    public function setUp()
    {
        //INIT TEST SUITE
        $kernel = static::createKernel();
        $kernel->boot();
        $this->em = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager');
        $this->repo = $this->em
            ->getRepository('PumukitSchemaBundle:Tag');

        //DELETE DATABASE TABLES
        $this->em->createQuery("DELETE PumukitSchemaBundle:Tag t")->getResult();
        $this->em->createQuery('DELETE Gedmo\Translatable\Entity\Translation et')->getResult();

    }

// TO DO: The tree was fine after creating and checking it, but some assertions would be nice.
    public function testCreateTree()
    {
        $uvigo = new Tag();
        $uvigo->setTitle('Uvigo_es');

        $cvigo = new Tag();
        $cvigo->setTitle('Campus Vigo_es');
        $cvigo->setParent($uvigo);

        $courense = new Tag();
        $courense->setTitle('Campus Ourense_es');
        $courense->setParent($uvigo);

        $cponte = new Tag();
        $cponte->setTitle('Campus Pontevedra_es');
        $cponte->setParent($uvigo);

        $cvigo = new Tag();
        $cvigo->setTitle('Teleco_es');
        $cvigo->setParent($cvigo);

        $this->em->persist($uvigo);
        $this->em->persist($cvigo);
        $this->em->persist($courense);
        $this->em->persist($cponte);
        $this->em->persist($cvigo);
        $this->em->flush();
    }

    public function testCreateMultipleTranslationsWithOneFlush()
    {
        $title_uvigo    = 'Uvigo';
        $title_uvigo_gl = 'Galego Uvigo_gl';
        $title_uvigo_en = 'English Uvigo_en';
        $desc_uvigo     = 'Descripción Uvigo';
        $desc_uvigo_gl  = 'Galego Descripción Uvigo_gl';
        $desc_uvigo_en  = 'English Description Uvigo_en';
        $title_cvigo    = 'Campus Vigo';
        $title_cvigo_gl = 'Galego Campus Vigo_gl';
        $title_cvigo_en = 'English Campus Vigo_en';
        $desc_cvigo     = 'Descripción Campus Vigo';
        $desc_cvigo_gl  = 'Galego Descripción Campus Vigo_gl';
        $desc_cvigo_en  = 'English Description Campus Vigo_en';
        $locale_en      = 'en-EN';
        $locale_gl      = 'gl';

        $uvigo = new Tag();
        $uvigo->setTitle($title_uvigo);
        $uvigo->setDescription($desc_uvigo);

        $cvigo = new Tag();
        $cvigo->setTitle($title_cvigo);
        $cvigo->setDescription($desc_cvigo);
        $cvigo->setParent($uvigo);

        $this->trepo = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');

        $this->trepo->translate($uvigo, 'title', $locale_gl, $title_uvigo_gl);
        $this->trepo->translate($uvigo, 'title', $locale_en, $title_uvigo_en);
        $this->trepo->translate($uvigo, 'description', $locale_gl, $desc_uvigo_gl);
        $this->trepo->translate($uvigo, 'description', $locale_en, $desc_uvigo_en);
        $this->trepo->translate($cvigo, 'title', $locale_gl, $desc_cvigo_gl);
        $this->trepo->translate($cvigo, 'title', $locale_en, $desc_cvigo_en);
        $this->trepo->translate($cvigo, 'description', $locale_gl, $desc_cvigo_gl);
        $this->trepo->translate($cvigo, 'description', $locale_en, $desc_cvigo_en);

        $this->em->persist($uvigo);
        $this->em->persist($cvigo);
        $this->em->flush();

        // Using _call magic instead of findOneBy(array('title' => $title_uvigo));
        $found_uvigo=$this->repo->findOneByTitle($title_uvigo);
        $this->assertEquals($title_uvigo, $found_uvigo->getTitle());
        $this->assertEquals($desc_uvigo, $found_uvigo->getDescription());

        $found_cvigo=$this->repo->findOneByDescription($desc_cvigo);
        $this->assertEquals($desc_cvigo, $found_cvigo->getDescription());

        /* $translations contains:
        Array (
            [de_de] => Array
                (
                    [title] => my title in de
                    [content] => my content in de
                )

            [en_us] => Array
                (
                    [title] => my title in en
                    [content] => my content in en
                )
        )*/
        $translations_uvigo = $this->trepo->findTranslations($found_uvigo);
        $this->assertEquals($title_uvigo_en, $translations_uvigo[$locale_en]['title']);
        $this->assertEquals($title_uvigo_gl, $translations_uvigo[$locale_gl]['title']);
        $this->assertEquals($desc_uvigo_en, $translations_uvigo[$locale_en]['description']);
        $this->assertEquals($desc_uvigo_gl, $translations_uvigo[$locale_gl]['description']);

// TO DO: check the utility of this property
        // $found_uvigo->setTranslatableLocale($locale_gl);
        // var_dump($found_uvigo->getTitle());
        // $found_uvigo->setTranslatableLocale($locale_en);
        // var_dump($found_uvigo->getTitle());
    }
}
