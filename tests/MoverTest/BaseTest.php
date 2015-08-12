<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendMoverTest;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;

/**
 * Class BaseTest
 *
 * @package ZendMoverTest
 */
abstract class BaseTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var string
     */
    protected $testDataPath;

    public function setUp()
    {
        parent::setUp();

        $this->serviceManager = $this->getServiceManager();
        $this->assertInstanceOf(\Zend\ServiceManager\ServiceManager::class, $this->serviceManager);

        $this->testDataPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "data/";
    }

    /**
     * @return ServiceManager
     */
    protected function getServiceManager()
    {
        $sm = \BootstrapTests::getServiceManager();

        return $sm;
    }

    public function testTicketsPathForTests()
    {
        $this->assertNotFalse(strpos($this->testDataPath, "module/Mover/tests/data/"));
    }

}
