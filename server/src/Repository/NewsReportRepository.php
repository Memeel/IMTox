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
        $newsReport = $entityManager->getRepository(NewsReport::class)->find($id);

        if (!$newsReport) {
            throw new \Exception('No news report found for id ' . $id);
        }

        $newsReport->setState($state);
        $entityManager->flush();
    }
}
