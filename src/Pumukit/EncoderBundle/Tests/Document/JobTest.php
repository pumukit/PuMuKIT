<?php

namespace Pumukit\EncoderBundle\Tests\Document;

use PHPUnit\Framework\TestCase;
use Pumukit\EncoderBundle\Document\Job;

/**
 * @internal
 * @coversNothing
 */
class JobTest extends TestCase
{
    public function testDefaults()
    {
        $job = new Job();

        $this->assertEquals(Job::STATUS_WAITING, $job->getStatus());
        $this->assertEquals(['en' => ''], $job->getI18nName());
        $this->assertEquals(0, $job->getDuration());
        $this->assertEquals('0', $job->getSize());
        $this->assertEquals('en', $job->getLocale());
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

        $this->assertEquals($mm_id, $job->getMmId());
        $this->assertEquals($language_id, $job->getLanguageId());
        $this->assertEquals($profile, $job->getProfile());
        $this->assertEquals($cpu, $job->getCpu());
        $this->assertEquals($url, $job->getUrl());
        $this->assertEquals($status, $job->getStatus());
        $this->assertEquals($priority, $job->getPriority());
        $this->assertEquals($name, $job->getName());
        $this->assertEquals($description, $job->getDescription());
        $this->assertEquals($timeini, $job->getTimeini());
        $this->assertEquals($timestart, $job->getTimestart());
        $this->assertEquals($timeend, $job->getTimeend());
        $this->assertEquals($pid, $job->getPid());
        $this->assertEquals($path_ini, $job->getPathIni());
        $this->assertEquals($path_end, $job->getPathEnd());
        $this->assertEquals($ext_ini, $job->getExtIni());
        $this->assertEquals($ext_end, $job->getExtEnd());
        $this->assertEquals($duration, $job->getDuration());
        $this->assertEquals($size, $job->getSize());
        $this->assertEquals($email, $job->getEmail());
        $this->assertEquals($initVars, $job->getInitVars());
        $this->assertEquals($locale, $job->getLocale());

        $descriptionI18n = ['en' => 'description', 'es' => 'descripción'];
        $nameI18n = ['en' => 'name', 'es' => 'nombre'];

        $job->setI18nDescription($descriptionI18n);
        $job->setI18nName($nameI18n);

        $this->assertEquals($descriptionI18n, $job->getI18nDescription());
        $this->assertEquals($nameI18n, $job->getI18nName());

        $this->assertEquals('Waiting', $job->getStatusText());
    }
}
