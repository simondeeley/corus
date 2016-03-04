<?php

namespace Corus\Framework\Http\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * DefaultController class.
 *
 * @extends Controller
 */
class DefaultController extends Controller
{    
    /**
     * Example of a simple HTTP controller that returns
     * a rendered Twig template wrapped in a Symfony
     * Response object.
     * 
     * @access public
     * @return Reponse  A Symfony Response object
     */
    public function index()
    {
        return array('message' => 'This is the default welcome page for the Corus Framework.');
    }
}
