<?php

namespace App\Controller;

use App\Entity\NewsReport;
use App\Form\NewsReportType;
use App\Repository\NewsReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account/article')]
final class NewsReportController extends AbstractController
{
    #[Route('/draft', name: 'show_drafts', methods: ['GET'])]
    public function index(NewsReportRepository $newsReportRepository): Response
    {
        return $this->render('news_report/draft.html.twig', [
            'draft_report' => $newsReportRepository->findArticle('draft')
        ]);
    }

    #[Route('/published', name: 'show_published', methods: ['GET'])]
    public function showPublished(NewsReportRepository $newsReportRepository): Response
    {
        return $this->render('news_report/published.html.twig', [
            'published_report' => $newsReportRepository->findArticle('published')
        ]);
    }

    #[Route('/create', name: 'create_report', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newsReport = new NewsReport();
        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applySaveAction($newsReport, $request->request->getString('save_action'));
            $entityManager->persist($newsReport);
            $entityManager->flush();

            return $this->redirectToRoute('show_drafts', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/new.html.twig', [
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'show_report', methods: ['GET'])]
    public function show(NewsReport $newsReport): Response
    {
        return $this->render('news_report/show.html.twig', [
            'news_report' => $newsReport,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit_report', methods: ['GET', 'POST'])]
    public function edit(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applySaveAction($newsReport, $request->request->getString('save_action'));
            $entityManager->flush();

            return $this->redirectToRoute('show_drafts', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/edit.html.twig', [
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete_report', methods: ['POST'])]
    public function delete(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$newsReport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($newsReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('show_drafts', [], Response::HTTP_SEE_OTHER);
    }

    // Auteurs: Sadok LAJMI, Mélina WANG
    private function applySaveAction(NewsReport $newsReport, string $saveAction): void
    {
        $now = new DateTimeImmutable();

        if ($newsReport->getCreatedAt() === null) {
            $newsReport->setCreatedAt($now);
        }

        if ($newsReport->getNumberOfViews() === null) {
            $newsReport->setNumberOfViews(0);
        }

        $newsReport->setUpdateAt($now);

        if ($saveAction === 'publish') {
            $newsReport->setState('published');
            $newsReport->setPublishedAt($now);
            return;
        }

        $newsReport->setState('draft');

        if ($newsReport->getPublishedAt() === null) {
            $newsReport->setPublishedAt($now);
        }
    }
}
