<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\DoctrineORMBundle\Entity\Revision;

class RevisionRepository extends ContentRepository {

    /**
     * @param ContentInterface $content
     * @param int $limit
     *
     * @return Revision[]
     */
    public function findForContent(ContentInterface $content, int $limit = 20) : array {

        if(empty($content->getId())) {
            return [];
        }

        return $this->findBy([
            'entityType' => $content->getType(),
            'entityId' => $content->getId(),
        ], [
            'version' => 'DESC',
        ], $limit);
    }

    /**
     * @param ContentInterface $content
     * @param int $version
     *
     * @return Revision|null
     */
    public function findOneForContent(ContentInterface $content, int $version) : ?Revision {

        if(empty($content->getId())) {
            return null;
        }

        return $this->findOneBy([
            'entityType' => $content->getType(),
            'entityId' => $content->getId(),
            'version' => $version,
        ]);
    }

    /**
     * @param string $id
     * @param string $type
     *
     * @return int
     */
    public function deleteAllForContent(string $id, string $type) : int {

        if(empty($id) || empty($type)) {
            return 0;
        }

        return $this->createQueryBuilder('revision')
            ->delete('UniteCMSDoctrineORMBundle:Revision', 'revision')
            ->where('revision.entityId = :entityId')
            ->andWhere('revision.entityType = :entityType')
            ->setParameters([
                'entityId' => $id,
                'entityType' => $type,
            ])->getQuery()->execute();

    }

    /**
     * @param ContentInterface $content
     * @param string $operation
     * @param UserInterface $user
     *
     * @return Revision
     */
    public function createRevisionForContent(ContentInterface $content, string $operation, UserInterface $user) : Revision {

        if(empty($content->getId())) {
            throw new InvalidArgumentException('Revisions can only be created for persisted content.');
        }

        $revisions = $this->findForContent($content, 1);
        $version = empty($revisions) ? 1 : $revisions[0]->getVersion() + 1;

        $revision = new Revision();
        $revision
            ->setEntityId($content->getId())
            ->setEntityType($content->getType())
            ->setData($content->getData())
            ->setVersion($version)
            ->setOperation($operation)
            ->setOperatorName($user->getUsername());

        if($user instanceof \UniteCMS\CoreBundle\Security\User\UserInterface) {
            $revision
                ->setOperatorId($user->getId())
                ->setOperatorType($user->getType());
        }

        return $revision;
    }

}
