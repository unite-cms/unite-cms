<?php


namespace UniteCMS\AdminBundle\Controller;

use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    public function app(DomainManager $domainManager) {
        return $this->render('@UniteCMSAdmin/admin/app.html.twig', [
            'domain' => $domainManager->current(),
        ]);
    }
}
