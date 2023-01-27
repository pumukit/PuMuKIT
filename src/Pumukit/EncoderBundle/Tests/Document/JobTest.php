<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\EncoderBundle\Document\Job;

/**
 * @internal
 *
 * @coversNothing
 */
class JobTest extends TestCase
{
    public function testDefaults()
    {
        $job = new Job();

        static::assertEquals(Job::STATUS_WAITING, $job->getStatus());
        static::assertEquals(['en' => ''], $job->getI18nName());
        static::assertEquals(0, $job->getDuration());
        static::assertEquals('0', $job->getSize());
        static::assertEquals('en', $job->getLocale());
    }

    public function testGetterAndSetter()
    {
        $job = new Job();

        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $language_id = 'es';
        $profile = 1;
        $cpu = 'local';
        $url = 'video/'.$mm_id.'/video1.avi';
        $status = Job::STATUS_WAITING;
        $priority = 1;
        $name = 'video1';
        $description = 'description1';
        $timeini = new \DateTime('now');
        $timestart = new \DateTime('now');
        $timeend = new \DateTime('now');
        $pid = 3;
        $path_ini = 'path/ini';
        $path_end = 'path/end';
        $ext_ini = 'ext/ini';
        $ext_end = 'ext/end';
        $duration = 40;
        $size = '12000';
        $email = 'test@mail.com';
        $initVars = ['ocurls' => ['presenter/master' => 'http://presentatermaster.com', 'presentation/master' => 'http://presentationmaster']];
        $locale = 'en';

        $job->setLocale('en');
        $job->setMmId($mm_id);
        $job->setLanguageId($language_id);
        $job->setProfile($profile);
        $job->setCpu($cpu);
        $job->setUrl($url);
        $job->setStatus($status);
        $job->setPriority($priority);
        $job->setName($name);
        $job->setDescription($description);
        $job->setTimeini($timeini);
        $job->setTimestart($timestart);
        $job->setTimeend($timeend);
        $job->setPid($pid);
        $job->setPathIni($path_ini);
        $job->setPathEnd($path_end);
        $job->setExtIni($ext_ini);
        $job->setExtEnd($ext_end);
        $job->setDuration($duration);
        $job->setSize($size);
        $job->setEmail($email);
        $job->setInitVars($initVars);

        static::assertEquals($mm_id, $job->getMmId());
        static::assertEquals($language_id, $job->getLanguageId());
        static::assertEquals($profile, $job->getProfile());
        static::assertEquals($cpu, $job->getCpu());
        static::assertEquals($url, $job->getUrl());
        static::assertEquals($status, $job->getStatus());
        static::assertEquals($priority, $job->getPriority());
        static::assertEquals($name, $job->getName());
        static::assertEquals($description, $job->getDescription());
        static::assertEquals($timeini, $job->getTimeini());
        static::assertEquals($timestart, $job->getTimestart());
        static::assertEquals($timeend, $job->getTimeend());
        static::assertEquals($pid, $job->getPid());
        static::assertEquals($path_ini, $job->getPathIni());
        static::assertEquals($path_end, $job->getPathEnd());
        static::assertEquals($ext_ini, $job->getExtIni());
        static::assertEquals($ext_end, $job->getExtEnd());
        static::assertEquals($duration, $job->getDuration());
        static::assertEquals($size, $job->getSize());
        static::assertEquals($email, $job->getEmail());
        static::assertEquals($initVars, $job->getInitVars());
        static::assertEquals($locale, $job->getLocale());

        $descriptionI18n = ['en' => 'description', 'es' => 'descripción'];
        $nameI18n = ['en' => 'name', 'es' => 'nombre'];

        $job->setI18nDescription($descriptionI18n);
        $job->setI18nName($nameI18n);

        static::assertEquals($descriptionI18n, $job->getI18nDescription());
        static::assertEquals($nameI18n, $job->getI18nName());

        static::assertEquals('Waiting', $job->getStatusText());
    }
}
