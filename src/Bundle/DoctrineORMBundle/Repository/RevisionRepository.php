<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface as UniteUserInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\DoctrineORMBundle\Entity\Revision;

class RevisionRepository extends ContentRepository {

    /**
     * @param ContentInterface $content
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     *
     * @return Revision[]
     */
    public function findForContent(ContentInterface $content, int $limit = 20, int $offset = 0, array $orderBy = ['version' => 'DESC']) : array {

        if(empty($content->getId())) {
            return [];
        }

        return $this->findBy([
            'entityType' => $content->getType(),
            'entityId' => $content->getId(),
        ], $orderBy, $limit, $offset);
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

        /**
         * @var Revision $revision
         */
        $revision = $this->findOneBy([
            'entityType' => $content->getType(),
            'entityId' => $content->getId(),
            'version' => $version,
        ]);

        return $revision;
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
     * @throws \Exception
     */
    public function createRevisionForContent(ContentInterface $content, string $operation, UserInterface $user = null) : Revision {

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
            ->setOperatorName($user ? $user->getUsername() : 'anon');

        if($user instanceof UniteUserInterface) {
            $revision
                ->setOperatorId($user->getId())
                ->setOperatorType($user->getType());
        }

        return $revision;
    }

}
