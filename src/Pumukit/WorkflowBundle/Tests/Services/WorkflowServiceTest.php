<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;

class ProfileServiceTest extends WebTestCase
{
    private $dm;
    private $repo;
    private $profileService;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb')->getManager();
    }

    public function setUp()
    {
        //$this->profileService = new ProfileService($this->getDemoProfiles(), $this->dm);
      
        $jobSerive = $this->getMock('Pumukit\EncoderBundle\Services\JobService');
        $profileService = $this->getMock('Pumukit\EncoderBundle\Services\ProfileService');
        $logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface');
        $this->workflowService = new WorkflowService($this->dm, $jobSerive, $profileService, $logger);
    }

    public function testA()
    {
    }
}