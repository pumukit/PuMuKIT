<?php

namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Person;
use PHPUnit\Framework\TestCase;

class PersonTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $email = 'email@email.com';
        $name = 'name';
        $web = 'web';
        $phone = 'phone';
        $honorific = 'Mr';
        $firm = 'firm';
        $post = 'post';
        $bio = 'Biography of this person';
        $locale = 'es';

        $person = new Person();

        $person->setLocale($locale);
        $person->setEmail($email);
        $person->setName($name);
        $person->setWeb($web);
        $person->setPhone($phone);
        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        $this->assertEquals($email, $person->getEmail());
        $this->assertEquals($name, $person->getName());
        $this->assertEquals($web, $person->getWeb());
        $this->assertEquals($phone, $person->getPhone());
        $this->assertEquals($honorific, $person->getHonorific());
        $this->assertEquals($firm, $person->getFirm());
        $this->assertEquals($post, $person->getPost());
        $this->assertEquals($bio, $person->getBio());
        $this->assertEquals($locale, $person->getLocale());

        $this->assertEquals($honorific.' '.$name, $person->getHName());
        $this->assertEquals($post.' '.$firm.' '.$bio, $person->getOther());
        $this->assertEquals($post.', '.$firm.', '.$bio, $person->getInfo());

        $bio = '';
        $person->setBio($bio);
        $this->assertEquals($post.', '.$firm, $person->getInfo());

        $honorificEs = 'Don';
        $firmEs = 'Firma de esta persona';
        $postEs = 'Post de esta persona';
        $bioEs = 'Biografía de esta persona';

        $i18nHonorific = ['en' => $honorific, 'es' => $honorificEs];
        $i18nFirm = ['en' => $firm, 'es' => $firmEs];
        $i18nPost = ['en' => $post, 'es' => $postEs];
        $i18nBio = ['en' => $bio, 'es' => $bioEs];

        $person->setI18nHonorific($i18nHonorific);
        $person->setI18nFirm($i18nFirm);
        $person->setI18nPost($i18nPost);
        $person->setI18nBio($i18nBio);

        $this->assertEquals($i18nHonorific, $person->getI18nHonorific());
        $this->assertEquals($i18nFirm, $person->getI18nFirm());
        $this->assertEquals($i18nPost, $person->getI18nPost());
        $this->assertEquals($i18nBio, $person->getI18nBio());

        $honorific = null;
        $firm = null;
        $post = null;
        $bio = null;

        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        $this->assertEquals($honorific, $person->getHonorific());
        $this->assertEquals($firm, $person->getFirm());
        $this->assertEquals($post, $person->getPost());
        $this->assertEquals($bio, $person->getBio());
    }

    public function testCloneResource()
    {
        $person = new Person();

        $this->assertEquals($person, $person->cloneResource());
    }
}
