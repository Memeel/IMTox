<?php

namespace App\Controller;

use App\Repository\NewsReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route("/", name:"app")]
    public function root(): Response
    {
        return $this->redirectToRoute("app_home");
    }

    #[Route('/home', name: 'app_home')]
    public function home(NewsReportRepository $newsReportRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == "admin",
            'articles'=> $newsReportRepository->find5LatestArticlesForHome(),
        ]);
    }
}
