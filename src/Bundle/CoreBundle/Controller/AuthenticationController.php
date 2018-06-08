<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
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
