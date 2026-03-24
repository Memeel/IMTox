<?php

namespace App\Controller;

use App\Repository\NewsReportRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route('/search', name: 'app_search')]
    public function search(Request $request, UserRepository $userRepository, NewsReportRepository $newsReportRepository): Response
    {
        $search = $request->query->get('search', '');
        $searchType = $request->query->get('search_type', '');

        $articles = [];
        $reporters = [];

        if ($searchType === 'reporters' || $searchType === '') {
            $qb = $userRepository->createQueryBuilder('u');
            $qb->andWhere('u.fullname LIKE :search AND u.role = :role')
                ->setParameter('search', '%'.$search.'%')
                ->setParameter('role', 'reporter');
            $reporters = $qb->getQuery()->getResult();
        }

        if ($searchType === 'articles' || $searchType === '') {
            $qb = $newsReportRepository->createQueryBuilder('n');
            $qb->andWhere('n.title LIKE :search OR n.content LIKE :search')
                ->setParameter('search', '%'.$search.'%');
            $articles = $qb->getQuery()->getResult();
        }

        $articlesCount = count($articles);
        $reportersCount = count($reporters);

        return $this->render('search/search.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == "admin",
            'articles' => $articles,
            'reporters' => $reporters,
            'articlesCount' => $articlesCount,
            'reportersCount' => $reportersCount,
            'searchType' => $searchType,
            'search' => $search
        ]);
    }
}
