<?php

namespace App\Repository;

use App\Entity\CacheApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CacheApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method CacheApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method CacheApi[]    findAll()
 * @method CacheApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CacheApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CacheApi::class);
    }

    public function deleteAll(){
        $query = $this->createQueryBuilder('e')
            ->delete()
            ->getQuery()
            ->execute();
        return $query;
    }

    // /**
    //  * @return CacheApi[] Returns an array of CacheApi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CacheApi
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
