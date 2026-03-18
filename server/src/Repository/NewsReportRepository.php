<?php

namespace App\Repository;

use App\Entity\NewsReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsReport>
 */
class NewsReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsReport::class);
    }

    public function findArticle(string $state): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT n
            FROM App\Entity\NewsReport n
            WHERE n.state = :state
            ORDER BY n.updateAt DESC'
        );
        $query->setParameter('state', $state);

        return $query->getResult();
    }   

    public function changeState(int $id, string $state): void
    {
        $entityManager = $this->getEntityManager();
       
        $query = $entityManager->createQuery(
            'UPDATE App\Entity\NewsReport n
            SET n.state = :state
            WHERE n.id = :id'
        );
        $query->setParameter('state', $state);
        $query->setParameter('id', $id);
        $query->execute();
    }
}
