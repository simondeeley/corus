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
     * Container
     * 
     * @var Container
     * @access protected
     */
    protected $container;
       
    /**
     * Setup the Container for testing.
     * 
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        vfsStream::setup('root', null, array(
            'test' => array(
                'config'   => array()
            )
        ));
        
        $this->container = Container::build(vfsStream::url('root/test'));
    }

    /**
     * Test Container is built correctly and that
     * it is caching files correctly.
     * 
     * @access public
     * @return void
     */
    public function testBuild()
    {
        $class = (string) 'Container'.'_'.md5(vfsStream::url('root/test')).'.php';

        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\Container::class, $this->container);
        $this->assertTrue(vfsStreamWrapper::getRoot()->getChild('test')->getChild('cache')->hasChild($class));     
    }
    
    /**
     * Test container throws correct 404 errors
     * 
     * @access public
     * @return void
     */    
    public function testNotFoundResponse()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->container->get('response'); 
    }
    
    /**
     * Test ContainerBuilder throws exception when
     * trying to use service_container definition
     *
     * @access public
     * @return void
     */
    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/^Cannot use service definition "([a-z_]*)?" when building the container.$/');

        $container = new Container;
        $container->setDefinition('service_container', new \Symfony\Component\DependencyInjection\Definition($container));
        $container->get('service_container');
        
        unset($container);
    } 
     
    /**
     * Unset variables not required anymore.
     * 
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->container);
    }
}
