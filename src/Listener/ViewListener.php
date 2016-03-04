<?php
    
namespace Corus\Framework\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ViewListener
{
    /**
     * Twig_Environment
     * 
     * @var mixed
     * @access protected
     */
    protected $templating;

    /**
     * Adds templating to our event listener.
     * 
     * @access public
     * @param \Twig_Environment $templating
     * @return void
     */
    public function __construct(\Twig_Environment $templating)
    {
        $this->templating = $templating;
    }

    /**
     * onKernelView function.
     * 
     * @access public
     * @param GetResponseForControllerResultEvent $event
     * @return void
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $data = $event->getControllerResult();

        $controller = $request->attributes->get('_controller');
        $controllerName = get_class($controller[0]);
        $controllerName = substr($controllerName,0,-10);
        $controllerName = explode('\\', $controllerName);
        $controllerName = end($controllerName);
        
        $actionName = $controller[1];
        
        if (stripos($actionName, 'action') !== false) {
            $actionName = substr($actionName,0,-6);
        }

        $viewPath = $controllerName . '/' . $actionName . '.html';

        $response = new Response($this->templating->render($viewPath, $data));

        $event->setResponse($response);
    }

}
