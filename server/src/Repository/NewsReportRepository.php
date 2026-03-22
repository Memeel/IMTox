<?php

namespace App\Repository;

use App\Entity\Enums\ArticleState;
use App\Entity\NewsReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<NewsReport>
 */
class NewsReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsReport::class);
    }
    public function getTopFiveArticles(): array
    {
        $qb = $this->createQueryBuilder('news_report');

        $qb->select('news_report')
        ->orderBy('news_report.numberOfViews', 'DESC')
        ->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }

    public function getTopFiveReporters(): array
    {
        $qb = $this->createQueryBuilder('nr');

        $qb->select('u.id, u.fullname, COUNT(nr.id) as numberOfArticles, SUM(nr.numberOfViews) as totalViews')
        ->join('nr.author', 'u')
        ->groupBy('u.id')
        ->orderBy('totalViews', 'DESC')
        ->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }

    public function findArticle(ArticleState $state, $authorId): array
    {
        if ($authorId === null) {
            return [];
        }
        
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT n
            FROM App\Entity\NewsReport n
            JOIN n.author u
            WHERE n.state = :state AND u.id = :authorId
            ORDER BY n.updateAt DESC'
        );
        $query->setParameter('state', $state);
        $query->setParameter('authorId', $authorId);

        return $query->getResult();
    }   

    public function changeState(int $id, ArticleState $state): void
    {
        $entityManager = $this->getEntityManager();
       
        $query = $entityManager->createQuery(
            'UPDATE App\Entity\NewsReport n
            SET 
                n.state = :state,
                n.updateAt = :updateAt,
                n.publishedAt = :publishedAt
            WHERE n.id = :id'
        );

        $currentDateTime = new DateTimeImmutable();
        $query->setParameter('state', $state);
        $query->setParameter('updateAt', $currentDateTime);
        $query->setParameter('publishedAt', $currentDateTime);
        $query->setParameter('id', $id);
        $query->execute();
    }
}
