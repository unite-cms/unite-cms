<?php


namespace UniteCMS\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use UniteCMS\CoreBundle\Domain\DomainManager;

class DevelopmentController extends AbstractController
{

    /**
     * @param \UniteCMS\CoreBundle\Domain\DomainManager $domainManager
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function playground(DomainManager $domainManager) {
        return $this->render('@UniteCMSCore/development/playground.html.twig', [
            'domain' => $domainManager->current(),
        ]);
    }
}
