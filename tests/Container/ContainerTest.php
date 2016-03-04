<?php

namespace Corus\Framework\Tests\Container;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Corus\Framework\Container\Container;

/**
 * ContainerTest class.
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{    
    public function setUp()
    {
        vfsStream::setup(array(
            'root' => array(
                'config' => array(
                    'services.yml' => '# Services file')
                )
            )
        );
    }

    /**
     *
     * @dataProvider providerTestBuild
     */
    public function testBuild(string $rootDir, bool $debug)
    {
        $container = Container::build($rootDir, $debug);
        $this->assertSame(\Symfony\Component\DependencyInjection\Container\ContainerInterface::class, get_class($container));
    }
    
    public function providerTestBuild()
    {
        return array(
            array(vfsStream::url('root'), true) 
        );
    }
    
}
