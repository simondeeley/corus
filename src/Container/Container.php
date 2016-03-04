<?php

namespace Corus\Framework\Container;

use Corus\Framework\Container\CompilerPass\EventDispatcherTagCompilerPass;
use Corus\Framework\Container\CompilerPass\RouterTagCompilerPass;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\Config\EnvParametersResource;

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
    const DEFAULT_CHARSET = 'UTF-8';

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
            throw new \InvalidArgumentException(sprintf('The service definition "%s" does not exist.', $id));
        }

        return parent::get($id, $invalidBehavior);
    }

    /**
     * Builds the container.
     * In debug mode, this function will check if the cached
     * container is valid and if not it will re-compile it.
     * 
     * @access public
     *
     * @param string $rootDir  The root path of the application
     * @param bool   $debug    Enable debug mode (defaults to false)
     * @return ContainerInterface   A dependency injection container
     */
    public static function build(string $rootDir, bool $debug = false)
    {
        $rootDir = realpath($rootDir) ?: $rootDir;
        
        $containerDefaultConfigDir = __DIR__.'/../Resources/config/';
        $containerUserConfigDir    = $rootDir.'/config/';
        $containerDebugConfigDir   = ($debug) ? $containerDefaultConfigDir.'debug/' : '';
         
        $class = (string) 'Container'.'_'.md5($rootDir.($debug ? 'Debug' : ''));
        $cache = new ConfigCache($rootDir.'/cache/'.$class.'.php', $debug);
        
        if (false === $cache->isFresh()) {            
            $container = new self(new ParameterBag(static::getParameters($rootDir, $debug)));
            $container->addCompilerPass(new RouterTagCompilerPass);
            $container->addCompilerPass(new EventDispatcherTagCompilerPass);
            $container->setProxyInstantiator(new RuntimeInstantiator);
            $container->addResource(new EnvParametersResource('APP__'));
            
            $loader = static::getLoader($container);
            foreach(compact('containerDefaultConfigDir', 'containerDebugConfigDir', 'containerUserConfigDir') as $path) {
                if (null !== $cont = $loader->load($path)) {
                    $container->merge($cont);
                }
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
     * Returns the kernel parameters.
     *
     * @access protected
     *
     * @param string $rootDir   The root path of the application
     * @param bool   $debug     True|False if debug mode enabled|disabled
     * @return array            An array of kernel parameters
     */
    protected static function getParameters(string $rootDir, bool $debug)
    {
        $env = array();
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'APP__')) {
                $env[strtolower(str_replace('_', '.', substr($key, 5)))] = $value;
            }
        }
        
        return array_merge(
            array(
                'kernel.root_dir' => $rootDir,
                'kernel.debug' => $debug,
                'kernel.cache_dir' => $rootDir.'/cache',
                'kernel.logs_dir' => $rootDir.'/logs',
                'kernel.charset' => self::DEFAULT_CHARSET,
            ),
            $env
        );
    }
    
    /**
     * Returns a collection of config file loaders.
     * 
     * @access protected
     *
     * @param ContainerInterface $container An instance of a dependency injection container
     * @return LoaderInterface              A loader capable of loading resource files
     */
    protected static function getLoader(ContainerInterface $container)
    {
        $locator = new FileLocator();
        $resolver = new LoaderResolver(array(
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ));

        return new DelegatingLoader($resolver);
    }
}
