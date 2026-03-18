<?php

namespace App\Controller;

use App\Repository\NewsReportRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, UserRepository $userRepository, NewsReportRepository $newsReportRepository): Response
    {
        $search = $request->query->get('search');
        $searchType = $request->query->get('search_type');

        $qb_article = $newsReportRepository->createQueryBuilder('n');

        $qb_reporter = $userRepository->createQueryBuilder('u');

        if ($searchType === 'reporters') {
             $qb_reporter->andWhere('u.fullname LIKE :search AND u.role = reporter')
             ->setParameter('search', '%'.$search.'%');
        }

        if ($searchType === 'articles') {
            $qb_article->andWhere('n.title LIKE :search OR n.content LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($searchType === '') {
            $qb_article->andWhere('n.title LIKE :search OR n.content LIKE :search')
            ->setParameter('search', '%'.$search.'%');

            $qb_reporter->andWhere('u.fullname LIKE :search AND u.role = reporter')
            ->setParameter('search', '%'.$search.'%');
        }

        $articles = $qb_article->getQuery()->getResult();
        $reporters = $qb_reporter->getQuery()->getResult();
        $articlesCount = count($articles);
        $reportersCount = count($reporters);

        return $this->render('admin/users.html.twig', [
            'articles' => $articles,
            'reporters' => $reporters,
            'articlesCount' => $articlesCount,
            'reportersCount' => $reportersCount,
        ]);
    }
}
