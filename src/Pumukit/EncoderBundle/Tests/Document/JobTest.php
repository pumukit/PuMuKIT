<?php

namespace Pumukit\EncoderBundle\Tests\Document;

use Pumukit\EncoderBundle\Document\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $job = new Job();

        $this->assertEquals(array('en' => ''), $job->getI18nName());
        $this->assertEquals(0, $job->getDuration());
        $this->assertEquals('0', $job->getSize());
        $this->assertEquals('en', $job->getLocale());
    }

    public function testGetterAndSetter()
    {
        $job = new Job();

        $mm_id = '54ad3f5e6e4cd68a278b4573';
        $language_id = 'es';
        $profile_id = 1;
        $cpu_id = 2;
        $url = 'video/'.$mm_id.'/video1.avi';
        $status_id = Job::STATUS_WAITING;
        $priority = 1;
        $name = 'video1';
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
        $locale = 'en';

        $job->setLocale('en');
        $job->setMmId($mm_id);
        $job->setLanguageId($language_id);
        $job->setProfileId($profile_id);
        $job->setCpuId($cpu_id);
        $job->setUrl($url);
        $job->setStatusId($status_id);
        $job->setPriority($priority);
        $job->setName($name);
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
        
        $this->assertEquals($mm_id, $job->getMmId());
        $this->assertEquals($language_id, $job->getLanguageId());
        $this->assertEquals($profile_id, $job->getProfileId());
        $this->assertEquals($cpu_id, $job->getCpuId());
        $this->assertEquals($url, $job->getUrl());
        $this->assertEquals($status_id, $job->getStatusId());
        $this->assertEquals($priority, $job->getPriority());
        $this->assertEquals($name, $job->getName());
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
        $this->assertEquals($locale, $job->getLocale());
    }
}