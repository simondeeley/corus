<?php

namespace Corus\Framework\Container\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Compiler pass to register tagged services for an event dispatcher.
 */
class EventDispatcherTagCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $service;

    /**
     * @var string
     */
    protected $listenerTag;

    /**
     * @var string
     */
    protected $subscriberTag;

    /**
     * Constructor.
     *
     * @param string $service           Service name of the event dispatcher in processed container
     * @param string $listenerTag       Tag name used for listener
     * @param string $subscriberTag     Tag name used for subscribers
     */
    public function __construct($service = 'event_dispatcher', $listenerTag = 'event_listener', $subscriberTag = 'event_subscriber')
    {
        $this->service = $service;
        $this->listenerTag = $listenerTag;
        $this->subscriberTag = $subscriberTag;
    }

    /**
     * process function.
     * 
     * @access public
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->service) && !$container->hasAlias($this->service)) {
            return;
        }
        $definition = $container->findDefinition($this->service);

        $this->processListenerTags($definition, $container);
        $this->processSubscriberTags($definition, $container);
    }
    
    /**
     * processListenerTags function.
     * 
     * @access private
     * @param mixed $definition
     * @param ContainerBuilder $container
     * @return void
     */
    private function processListenerTags($definition, ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds($this->listenerTag) as $id => $events) {
            $def = $this->getDefinition($id, $container);

            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace_callback(array(
                        '/(?<=\b)[a-z]/i',
                        '/[^a-z0-9]/i',
                    ), function ($matches) { return strtoupper($matches[0]); }, $event['event']);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }

                $definition->addMethodCall('addListener', array($event['event'], array($def, $event['method']), $priority));
            }
        }   
    }
    
    /**
     * processSubscriberTags function.
     * 
     * @access private
     * @param mixed $definition
     * @param ContainerBuilder $container
     * @return void
     */
    private function processSubscriberTags($definition, ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds($this->subscriberTag) as $id => $attributes) {
            $def = $this->getDefinition($id, $container);
            $definition->addMethodCall('addSubscriber', array($def));
        }
    }
    
    /**
     * getDefinition function.
     * 
     * @access private
     * @param string $id
     * @return void
     */
    private function getDefinition(string $id, ContainerBuilder $container)
    {
        $def = $container->getDefinition($id);
        if (!$def->isPublic()) {
            throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
        }

        if ($def->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
        }
        
        return $def;
    }
}
