<?php

namespace UnitedCMS\CoreBundle\Service;


use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\SettingType;

class UnitedCMSManager
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var \UnitedCMS\CoreBundle\Entity\Organization
     */
    private $organization;

    /**
     * @var \UnitedCMS\CoreBundle\Entity\Domain
     */
    private $domain;

    /**
     * @var bool
     */
    private $initialized;

    public function __construct(EntityManager $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->initialized = false;
    }

    /**
     * Loads the core information (id, title, identifier) of the current
     * organization, domain and domain's content and setting types. Only loads
     * the data once per request.
     *
     * The partly filled objects are not managed by doctrine, so they will not
     * get altered if any of the managed objects get altered.
     */
    private function initialize()
    {
        $this->initialized = true;

        $request = $this->requestStack->getCurrentRequest();

        if(!$request) {
            return;
        }

        // Get organization and domain form current request.
        $requestOrganization = $request->attributes->get('organization');
        $requestDomain = $request->attributes->get('domain');

        if ($requestOrganization instanceof Organization) {
            $requestOrganizationOriginal = $this->em->getUnitOfWork()->getOriginalEntityData($requestOrganization);
            if(!empty($requestOrganizationOriginal['identifier'])) {
                $organizationIdentifier = $requestOrganizationOriginal['identifier'];
            } else {
                $organizationIdentifier = $requestOrganization->getIdentifier();
            }
        } else {
            $organizationIdentifier = $requestOrganization;
        }

        if ($requestDomain instanceof Domain) {
            $requestDomainOriginal = $this->em->getUnitOfWork()->getOriginalEntityData($requestDomain);
            if(!empty($requestOrganizationOriginal['identifier'])) {
                $domainIdentifier = $requestDomainOriginal['identifier'];
            } else {
                $domainIdentifier = $requestDomain->getIdentifier();
            }
        } else {
            $domainIdentifier = $requestDomain;
        }

        // Get organization information from db.
        $data = $this->em->createQueryBuilder()
            ->select('o.id', 'o.identifier', 'o.title')
            ->from('UnitedCMSCoreBundle:Organization', 'o')
            ->where('o.identifier = :organization')
            ->getQuery()->execute(['organization' => $organizationIdentifier]);

        if (!count($data) == 1) {
            return;
        }

        $this->organization = new Organization();
        $this->organization->setId($data[0]['id'])->setIdentifier($data[0]['identifier'])->setTitle($data[0]['title']);

        // Get all domains of this organization from db.
        $data = $this->em->createQueryBuilder()
            ->select('d.id', 'd.identifier', 'd.title')
            ->from('UnitedCMSCoreBundle:Domain', 'd')
            ->where('d.organization = :organization')
            ->getQuery()->execute(['organization' => $this->organization]);

        foreach ($data as $row) {
            $domain = new Domain();
            $domain->setId($row['id'])->setIdentifier($row['identifier'])->setTitle($row['title']);
            $this->organization->addDomain($domain);
        }

        // Get contentTypes and settingTypes for the current domain.
        if ($domainIdentifier) {

            // Try to find the current domain.
            foreach ($this->organization->getDomains() as $domain) {
                if ($domain->getIdentifier() === $domainIdentifier) {
                    $this->domain = $domain;
                }
            }

            if (!$this->domain) {
                return;
            }

            $data = $this->em->createQueryBuilder()
                ->select('ct.id', 'ct.identifier', 'ct.title', 'ct.contentLabel', 'ct.icon', 'ct.permissions')
                ->from('UnitedCMSCoreBundle:ContentType', 'ct')
                ->leftJoin('ct.domain', 'd')
                ->leftJoin('ct.views', 'co')
                ->where('ct.domain = :domain')
                ->andWhere('d.organization = :organization')
                ->orderBy('ct.weight')
                ->getQuery()->execute(['organization' => $this->organization, 'domain' => $this->domain]);

            foreach ($data as $row) {
                $contentType = new ContentType();
                $contentType->setId($row['id'])->setIdentifier($row['identifier'])->setTitle($row['title'])->setContentLabel($row['contentLabel'])->setIcon($row['icon'])->setPermissions($row['permissions']);

                // Get views for this contentType.
                $viewData = $this->em->createQueryBuilder()
                    ->select('v.id', 'v.identifier', 'v.title', 'v.type', 'v.icon')
                    ->from('UnitedCMSCoreBundle:View', 'v')
                    ->leftJoin('v.contentType', 'ct')
                    ->where('ct.id = :ct')
                    ->getQuery()->execute(['ct' => $contentType->getId()]);

                // Remove the default 'all' view from the contentType so it can be replaced with persisted views.
                $contentType->getViews()->clear();

                foreach($viewData as $viewRow) {
                    $view = new View();
                    $view->setId($viewRow['id'])->setIdentifier($viewRow['identifier'])->setTitle($viewRow['title'])->setType($viewRow['type'])->setIcon($viewRow['icon']);
                    $contentType->addView($view);
                }

                $this->domain->addContentType($contentType);
            }

            $data = $this->em->createQueryBuilder()
                ->select('st.id', 'st.identifier', 'st.title', 'st.icon', 'st.permissions')
                ->from('UnitedCMSCoreBundle:SettingType', 'st')
                ->leftJoin('st.domain', 'd')
                ->where('st.domain = :domain')
                ->andWhere('d.organization = :organization')
                ->orderBy('st.weight')
                ->getQuery()->execute(['organization' => $this->organization, 'domain' => $this->domain]);

            foreach ($data as $row) {
                $settingType = new SettingType();
                $settingType->setId($row['id'])->setIdentifier($row['identifier'])->setTitle($row['title'])->setIcon($row['icon'])->setPermissions($row['permissions']);
                $this->domain->addSettingType($settingType);
            }
        }
    }

    /**
     * Get the current organization.
     *
     * @return \UnitedCMS\CoreBundle\Entity\Organization
     */
    public function getOrganization()
    {

        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->organization;
    }

    /**
     * Get the current domain.
     *
     * @return \UnitedCMS\CoreBundle\Entity\Domain
     */
    public function getDomain()
    {

        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->domain;
    }

}