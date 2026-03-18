<?php

namespace App\Controller;

use App\Entity\NewsReport;
use App\Form\NewsReportType;
use App\Repository\NewsReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/news/report')]
final class NewsReportController extends AbstractController
{
    #[Route(name: 'app_news_report_index', methods: ['GET'])]
    public function index(NewsReportRepository $newsReportRepository): Response
    {
        return $this->render('news_report/index.html.twig', [
            'news_reports' => $newsReportRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_news_report_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newsReport = new NewsReport();
        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newsReport);
            $entityManager->flush();

            return $this->redirectToRoute('app_news_report_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/new.html.twig', [
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_news_report_show', methods: ['GET'])]
    public function show(NewsReport $newsReport): Response
    {
        return $this->render('news_report/show.html.twig', [
            'news_report' => $newsReport,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_news_report_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_news_report_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/edit.html.twig', [
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_news_report_delete', methods: ['POST'])]
    public function delete(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$newsReport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($newsReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_news_report_index', [], Response::HTTP_SEE_OTHER);
    }
}
