<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AuthenticationController extends Controller
{

    /**
     * @Route("/login")
     * @Method({"GET", "POST"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        // Redirect the user to / if already authenticated.
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('unitecms_core_index');
        }

        // get the login error if there is one
        $error = $this->get('security.authentication_utils')->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $this->get('security.authentication_utils')->getLastUsername();

        return $this->render(
            'UniteCMSCoreBundle:Authentication:login.html.twig',
            array(
                'last_username' => $lastUsername,
                'error' => $error,
            )
        );
    }
}
