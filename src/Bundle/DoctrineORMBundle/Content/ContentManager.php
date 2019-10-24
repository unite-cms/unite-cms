<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;
use UniteCMS\DoctrineORMBundle\Entity\Content;
use UniteCMS\DoctrineORMBundle\Entity\Revision;

class ContentManager implements ContentManagerInterface
{
    const ENTITY = Content::class;

    /**
     * @var \Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected $registry;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * ContentManager constructor.
     *
     * @param \Symfony\Bridge\Doctrine\RegistryInterface $registry
     * @param \Symfony\Component\Security\Core\Security $security
     */
    public function __construct(RegistryInterface $registry, Security $security) {
        $this->registry = $registry;
        $this->security = $security;
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return ObjectManager
     */
    protected function em(Domain $domain) : ObjectManager {
        return $this->registry->getManager($domain->getId());
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return \UniteCMS\DoctrineORMBundle\Repository\ContentRepository
     */
    protected function repository(Domain $domain) : ObjectRepository {
        return $this->em($domain)->getRepository(static::ENTITY);
    }

    /**
     * {@inheritDoc}
     */
    public function find(Domain $domain, string $type, ContentFilterInput $filter = null, array $orderBy = [], int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {

        // TODO: Criteria
        $criteria = [];

        // TODO: Criteria
        $orderBy = [];

        return new ContentResult($this->repository($domain), $type, $criteria, $orderBy, $limit, $offset, $includeDeleted, $resultFilter);
    }

    /**
     * {@inheritDoc}
     */
    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false): ?ContentInterface {
        return $this->repository($domain)->typedFind($type, $id, $includeDeleted);
    }

    /**
     * {@inheritDoc}
     */
    public function create(Domain $domain, string $type): ContentInterface {
        $class = static::ENTITY;
        return new $class($type);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Domain $domain, ContentInterface $content, array $inputData = [], bool $persist = false): ContentInterface {
        return $content->setData($inputData);
    }

    /**
     * {@inheritDoc}
     */
    public function revert(Domain $domain, ContentInterface $content, int $version) : ContentInterface {

        $revision = $this->em($domain)
            ->getRepository(Revision::class)
            ->findOneForContent($content, $version);

        if(!$revision) {
            throw new InvalidContentVersionException();
        }

        $content->setData($revision->getData());

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function revisions(Domain $domain, ContentInterface $content, int $limit = 20) : array {
        return $this->em($domain)
            ->getRepository(Revision::class)
            ->findForContent($content, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Domain $domain, ContentInterface $content, bool $persist = false): ContentInterface {
        return $content;    // Delete will be handled in persist.
    }

    public function recover(Domain $domain, ContentInterface $content) : ContentInterface {
        return $content->setDeleted(null);
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Domain $domain, ContentInterface $content, string $persistType) : void {

        if(empty($content->getId())) {
            $this->em($domain)->persist($content);
        }

        $contentId = $content->getId();
        $contentType = $content->getType();
        $softDelete = false;

        if($persistType === ContentEvent::DELETE) {

            // Hard delete content.
            if(!empty($content->getDeleted())) {
                $this->em($domain)->remove($content);

            // Soft delete content.
            } else {
                $content->setDeleted(new DateTime());
                $softDelete = true;
            }
        }

        $this->em($domain)->flush($content);

        // Create revision entry for all operations but hard delete.
        if($persistType !== ContentEvent::DELETE || $softDelete) {
            $revision = $this->em($domain)
                ->getRepository(Revision::class)
                ->createRevisionForContent($content, $persistType, $this->security->getUser());
            $this->em($domain)->persist($revision);
            $this->em($domain)->flush($revision);

        // On hard delete, remove all revision entries.
        } else {
            $this->em($domain)
                ->getRepository(Revision::class)
                ->deleteAllForContent($contentId, $contentType);

        }
    }
}
