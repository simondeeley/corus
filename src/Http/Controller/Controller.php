<?php

namespace Corus\Framework\Http\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Abstract Controller class.
 * 
 * @abstract
 */
abstract class Controller
{
    /** 
     * @var Request 
     *
     */
    protected $request;

    /** 
     * @var Twig_Environment
     *
     */
    protected $templating;

    /** 
     * @var EntityManager
     *
     */
    protected $entityManager;

    /**
     * @var UrlGenerator
     *
     */
    protected $urlGenerator;

    /**
     * Build a base controller with some common web 
     * application functions and features.
     * 
     * @access public
     *
     * @param Request           $request        A Symfony HttpFoundation Response object
     * @param \Twig_Environment $templating     Twig templating environment
     * @param EntityManager     $entityManager  Doctrine entity manager
     * @param UrlGenerator      $urlGenerator   Symfony routing component URL generator
     *
     * @return void
     */
    public function __construct(Request $request, \Twig_Environment $templating, EntityManager $entityManager, UrlGenerator $urlGenerator)
    {
        $this->request = $request;
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }
}
