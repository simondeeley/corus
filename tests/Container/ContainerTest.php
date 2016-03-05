<?php

namespace Corus\Framework\Tests\Container;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Corus\Framework\Container\Container;

/**
 * ContainerTest class.
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{     
    /**
     * setUp function.
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        $structure = array('test' => array('config'   => array()));
        vfsStream::setup('root', null, $structure);
    }

    /**
     * Test Container is built correctly and that
     * it is caching files correctly.
     *
     * @dataProvider providerTestBuild
     */
    public function testBuild($rootDir, $debug)
    {
        $container = Container::build($rootDir, $debug);

        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\Container::class, $container);
        $this->assertTrue(vfsStreamWrapper::getRoot()->getChild('test')->getChild('cache')->hasChildren());      
    }
    
    /**
     * Test container throws correct 404 errors
     *
     * @dataProvider providerTestBuild
     */    
    public function testNotFoundResponse($rootDir, $debug)
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        $container = Container::build($rootDir, $debug);
        $container->get('response'); 
    }
    
    /**
     * providerTestBuild function.
     * 
     * @access public
     * @return void
     */
    public function providerTestBuild()
    {
        return array(
            array(vfsStream::url('root/test'), true),
            array(vfsStream::url('root/test'), false)
        );
    }
}
