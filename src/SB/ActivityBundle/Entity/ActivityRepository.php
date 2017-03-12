<?php

namespace SB\ActivityBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ActivityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ActivityRepository extends EntityRepository
{
    public function fetchAll($id_user, array $list_friends, $limit)
    {
        $qb = $this->createQueryBuilder('a');

        $qb
            ->join('a.user', 'user')
            ->where('a.user = :id_user')
            ->setParameter('id_user', $id_user)
        ;
        foreach ($list_friends as $key => $friend) {
            $qb
                ->orWhere('a.user = :friend' . $key)
                ->setParameter('friend' . $key, $friend->getId())
            ;
        }
        $qb
            ->orderBy('a.dateActivity', 'DESC')
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }
}
