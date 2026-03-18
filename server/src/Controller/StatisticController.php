<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NewsReportRepository;

final class StatisticController extends AbstractController
{

    #[Route('/stats', name: 'app_stats')]
    public function stats(Request $request, NewsReportRepository $newsRepo)
    {
        $articles = $newsRepo->getTopFiveArticles();
        $reporters = $newsRepo->getTopFiveReporters();

                // Articles
        $articleTitles = [];
        $articleViews = [];

        foreach ($articles as $article) {
            $articleTitles[] = $article->getTitle();
            $articleViews[] = (int) $article->getNumberOfViews();
        }

        // Reporters
        $reporterNames = [];
        $reporterViews = [];

        foreach ($reporters as $reporter) {
            $reporterNames[] = $reporter['fullname'];  
            $reporterViews[] = (int) $reporter['totalViews']; 
        }

        return $this->render('stats/stats.html.twig', [
            'articles' => $articles,
            'reporters' => $reporters,
            'articleTitles' => json_encode($articleTitles),
            'articleViews' => json_encode($articleViews),
            'reporterNames' => json_encode($reporterNames),
            'reporterViews' => json_encode($reporterViews),
        ]);
    }

}