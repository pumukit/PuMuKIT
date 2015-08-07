<?php

namespace Pumukit\WorkflowBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\WorkflowBundle\Services\WorkflowService;

/**
 * @IgnoreAnnotation("dataProvider")
 */
class WorkflowServiceTest extends WebTestCase
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
      
        $jobSerive = $this->getMockBuilder('Pumukit\EncoderBundle\Services\JobService')
                          ->disableOriginalConstructor()
                          ->getMock();
        $profileService = $this->getMockBuilder('Pumukit\EncoderBundle\Services\ProfileService')
                               ->disableOriginalConstructor()
                               ->getMock();              
        $logger = $this->getMockBuilder('Symfony\Component\HttpKernel\Log\LoggerInterface')
                       ->disableOriginalConstructor()
                       ->getMock();
                
        $this->workflowService = new WorkflowService($this->dm, $jobSerive, $profileService, $logger);
    }


    /**
     * @dataProvider providerTestFoo
     */
    public function testFoo($variableOne, $variableTwo)
    {
        // 
        dump(2);
        dump(func_get_args());       
    }

    public function providerTestFoo()
    {
        return array(
            array('test 1, variable one', 'test 1, variable two'),
            array('test 2, variable one', 'test 2, variable two'),
            array('test 3, variable one', 'test 3, variable two'),
            array('test 4, variable one', 'test 4, variable two'),
            array('test 5, variable one', 'test 5, variable two'),
        );
    }

    
    /**
     * @dataProvider providerTestGetTargets
     */
    public function testGetTargets()
    {
        dump(1);
        dump(func_get_args());
        $this->assertEquals(1, 1);
        //$this->assertEquals($out, $this->invokeMethod($this->workflowService, 'getTargets', array($in)));
    }

    public function providerTestGetTargets()
    {
        
        return array(
            array(1,2)
        );
        return array(        
            array('', array('standard' => array(), 'force' => array())),
            array('TAG', array('standard' => array(), 'force' => array())),
        );
    }


    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}