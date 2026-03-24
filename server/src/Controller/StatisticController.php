<?php
namespace App\Controller;

use App\Entity\Enums\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NewsReportRepository;

final class StatisticController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route('/stats', name: 'app_stats')]
    public function stats(NewsReportRepository $newsRepo)
    {
        $articles = $newsRepo->getTopFiveArticles();
        $reporters = $newsRepo->getTopFiveReporters();

        return $this->render('stats/stats.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'articles' => $articles,
            'reporters' => $reporters,
        ]);
    }
}