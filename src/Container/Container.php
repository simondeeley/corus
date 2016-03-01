<?php

namespace Corus\Framework\Container;

use Corus\Framework\Container\CompilerPass\RouterTagCompilerPass;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Constructs an instance implementing the ContainerInterface class
 * this is then cached and dumped to plain PHP for performance. We
 * also parse and add environment variables into the container to
 * ease setup and configuration.
 * 
 * @extends ContainerBuilder
 */
class Container extends ContainerBuilder
{
    /**
     * Builds the container.
     * In debug mode, this function will check if the cached
     * container is valid and if not it will re-compile it.
     * 
     * @access public
     * @static
     *
     * @param string $rootPath  The root path of the application
     * @param bool   $debug     Enabled debug mode (defaults to false)
     *
     * @return ContainerInterface   A dependency injection container
     */
    public static function build(string $rootPath, bool $debug = false)
    {        
        $class = (string) 'Container'.'_'.md5($rootPath.($debug ? 'Debug' : ''));
        $cache = new ConfigCache($rootPath.'/cache/'.$class.'.php', $debug);
        
        if (false === $cache->isFresh()) {
            $container = new self();
            $container->addCompilerPass(new RouterTagCompilerPass());
            $container->setProxyInstantiator(new RuntimeInstantiator());
            $container->setParameter('root_path', $rootPath);

            foreach ($_SERVER as $key => $value) {
                if (0 === strpos($key, 'APP__')) {
                    $container->setParameter(strtolower(str_replace('_', '.', substr($key, 5))), $value);
                }
            }
            
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('services.yml');
            
            if ($debug) {
                $loader->load('debug.yml');
            }
            
            $config = new YamlFileLoader($container, new FileLocator($rootPath . '/config'));
            if (null !== $cont = $config->load('services.yml')) {
                $container->merge($cont);
            }
            
            $container->compile();
            
            $dumper = new PhpDumper($container);
            $dumper->setProxyDumper(new ProxyDumper(md5($cache->getPath())));
            $content = $dumper->dump(array('class' => $class, 'base_class' => 'Container', 'file' => $cache->getPath()));
            
            $cache->write($content, $container->getResources());
        }
        
        require_once $cache->getPath();

        return new $class;
    }

    /**
     * get function.
     * 
     * @access public
     * @param mixed $id
     * @param mixed $invalidBehavior (default: ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
     * @return void
     */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if (strtolower($id) == 'service_container') {
            if (ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE !== $invalidBehavior) {
                return;
            }
            throw new InvalidArgumentException(sprintf('The service definition "%s" does not exist.', $id));
        }

        return parent::get($id, $invalidBehavior);
    }
}
