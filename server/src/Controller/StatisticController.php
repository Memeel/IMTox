<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NewsReportRepository;

final class StatisticController extends AbstractController
{
    #[Route('/stats', name: 'app_stats')]
    public function stats(NewsReportRepository $newsRepo)
    {
        $articles = $newsRepo->getTopFiveArticles();
        $reporters = $newsRepo->getTopFiveReporters();

        return $this->render('stats/stats.html.twig', [
            'articles' => $articles,
            'reporters' => $reporters,
        ]);
    }
}