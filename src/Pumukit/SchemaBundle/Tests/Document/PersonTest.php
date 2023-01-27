<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Person;

/**
 * @internal
 *
 * @coversNothing
 */
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

        static::assertEquals($email, $person->getEmail());
        static::assertEquals($name, $person->getName());
        static::assertEquals($web, $person->getWeb());
        static::assertEquals($phone, $person->getPhone());
        static::assertEquals($honorific, $person->getHonorific());
        static::assertEquals($firm, $person->getFirm());
        static::assertEquals($post, $person->getPost());
        static::assertEquals($bio, $person->getBio());
        static::assertEquals($locale, $person->getLocale());

        static::assertEquals($honorific.' '.$name, $person->getHName());
        static::assertEquals($post.' '.$firm.' '.$bio, $person->getOther());
        static::assertEquals($post.', '.$firm.', '.$bio, $person->getInfo());

        $bio = '';
        $person->setBio($bio);
        static::assertEquals($post.', '.$firm, $person->getInfo());

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

        static::assertEquals($i18nHonorific, $person->getI18nHonorific());
        static::assertEquals($i18nFirm, $person->getI18nFirm());
        static::assertEquals($i18nPost, $person->getI18nPost());
        static::assertEquals($i18nBio, $person->getI18nBio());

        $honorific = '';
        $firm = '';
        $post = '';
        $bio = '';

        $person->setHonorific($honorific);
        $person->setFirm($firm);
        $person->setPost($post);
        $person->setBio($bio);

        static::assertEquals($honorific, $person->getHonorific());
        static::assertEquals($firm, $person->getFirm());
        static::assertEquals($post, $person->getPost());
        static::assertEquals($bio, $person->getBio());
    }

    public function testCloneResource()
    {
        $person = new Person();

        static::assertEquals($person, $person->cloneResource());
    }
}
