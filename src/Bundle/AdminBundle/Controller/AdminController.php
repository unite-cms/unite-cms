<?php


namespace UniteCMS\AdminBundle\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use UniteCMS\CoreBundle\EventSubscriber\CreateJWTTokenSubscriber;

class AdminController extends AbstractController
{
    public function app(DomainManager $domainManager, CreateJWTTokenSubscriber $tokenSubscriber, JWTTokenManagerInterface $tokenManager) {

        $domain = $domainManager->current();

        /*
        TODO: Add optional public user config for completely hidden APIs.
        $username = 'PUBLIC';
        $publicUser = $domain->getUserManager()->findByUsername($domain, 'Token', $username);
        $tokenSubscriber->setNextTTL($domain->getJwtTTLShortLiving());
        $publicToken = $tokenManager->create($publicUser);*/
        $publicToken = '';

        return $this->render('@UniteCMSAdmin/admin/app.html.twig', [
            'domain' => $domain,
            'publicToken' => $publicToken,
        ]);
    }
}
