<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.05.18
 * Time: 17:44
 */

namespace UniteCMS\RegistrationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{

    /**
     * @Route("/registration")
     * @Method({"GET", "POST"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registrationAction(Request $request)
    {
        // TODO: Implement
        return new Response(200);
    }
}