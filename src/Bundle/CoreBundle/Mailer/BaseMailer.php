<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Mailer;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use UniteCMS\CoreBundle\Domain\DomainManager;

abstract class BaseMailer
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var UrlGeneratorInterface $urlGenerator
     */
    protected $urlGenerator;

    /**
     * @var Swift_Mailer $mailer
     */
    protected $mailer;

    /**
     * @var Environment $twig
     */
    protected $twig;

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var $defaultMailerFrom
     */
    protected $defaultMailerFrom;

    public function __construct(DomainManager $domainManager, Swift_Mailer $mailer, Environment $twig, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, string $defaultMailerFrom)
    {
        $this->domainManager = $domainManager;
        $this->urlGenerator = $urlGenerator;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->defaultMailerFrom = $defaultMailerFrom;
    }

    /**
     * @return string
     */
    protected function baseUrl() : string {
        try {
            return $this->urlGenerator->generate('unite_cms_admin', [
                'unite_domain' => $this->domainManager->current()->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (RouteNotFoundException $exception) {
            return $this->urlGenerator->generate('unite_cms_core_api', [
                'unite_domain' => $this->domainManager->current()->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }

    /**
     * @param string|null $url
     * @param $fallbackPath
     * @return string|null
     */
    protected function defaultUrl(?string $url = null, $fallbackPath) {
        return empty($url) ? ($this->baseUrl() . $fallbackPath) : $url;
    }
}
