<?php

namespace App\Controller;

use App\Entity\Enums\ArticleState;
use App\Entity\Enums\UserRole;
use App\Entity\NewsReport;
use App\Form\NewsReportType;
use App\Repository\NewsReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Auteurs: Sadok LAJMI, Mélina WANG
final class NewsReportController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route('/account/articles/{state}', name: 'show_articles', methods: ['GET'])]
    public function showArticles(string $state, NewsReportRepository $newsReportRepository): Response
    {
        $sessionAuthorId = $this->requestStack->getSession()->get("id");

        if ($sessionAuthorId === null) {
            return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
        }

        $articleState = ArticleState::tryFrom($state);
        if (!$articleState) {
            throw $this->createNotFoundException('Invalid article state.');
        }

        return $this->render("news_report/my_articles.html.twig", [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'news_reports' => $newsReportRepository->findArticle($articleState, $sessionAuthorId),
            'state' => $state
        ]);
    }

    #[Route('/account/article/create', name: 'create_report', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $sessionAuthorId = $request->getSession()->get('id');
        if ($sessionAuthorId === null) {
            return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
        }

        $newsReport = new NewsReport();
        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isCsrfTokenValid('create_edit', $request->getPayload()->getString('_token'))) {
            $author = $userRepository->find($sessionAuthorId);
            if ($author === null) {
                return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
            }

            $newsReport->setAuthor($author);

            $this->applySaveAction($newsReport, $request->request->getString('save_action'));
            $entityManager->persist($newsReport);
            $entityManager->flush();

            return $this->redirectToRoute('show_articles', ['state' => $newsReport->getState()->value, 'authorId' => $request->getSession()->get('id')], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/new.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/article/show/{id}', name: 'show_report', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        $sessionAuthorId = $request->getSession()->get('id');

        if ($newsReport->getState() !== ArticleState::PUBLISHED && $newsReport->getAuthor()?->getId() !== (int) $sessionAuthorId) {
            throw $this->createAccessDeniedException('This article is not publicly available.');
        }

        $session = $request->getSession();
        $viewed = $session->get('viewed_articles', []);

        if (!in_array($newsReport->getId(), $viewed)) {
            $newsReport->setNumberOfViews($newsReport->getNumberOfViews() + 1);
            $entityManager->flush();

            $viewed[] = $newsReport->getId();
            $session->set('viewed_articles', $viewed);
        }
        return $this->render('news_report/show.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'news_report' => $newsReport,
        ]);
    }

    #[Route('/article/edit/{id}', name: 'edit_report', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        $sessionAuthorId = $request->getSession()->get('id');
        if ($sessionAuthorId === null) {
            return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
        }

        if ($newsReport->getAuthor()?->getId() !== (int) $sessionAuthorId) {
            throw $this->createAccessDeniedException('You can only edit your own articles.');
        }

        $form = $this->createForm(NewsReportType::class, $newsReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isCsrfTokenValid('create_edit' . $newsReport->getId(), $request->getPayload()->getString('_token'))) {
            $this->applySaveAction($newsReport, $request->request->getString('save_action'));
            $entityManager->flush();

            return $this->redirectToRoute('show_articles', ['state' => $newsReport->getState()->value, 'authorId' => $request->getSession()->get('id')], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news_report/edit.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'news_report' => $newsReport,
            'form' => $form,
        ]);
    }

    #[Route('/article/change-state/{id}/{state}', name: 'change_state', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function changeState(Request $request, string $state, NewsReport $newsReport, NewsReportRepository $newsReportRepository, EntityManagerInterface $entityManager): Response
    {
        $sessionAuthorId = $request->getSession()->get('id');
        if ($sessionAuthorId === null) {
            return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
        }

        if ($newsReport->getAuthor()?->getId() !== (int) $sessionAuthorId) {
            throw $this->createAccessDeniedException('You can only change state of your own articles.');
        }

        $articleState = ArticleState::tryFrom($state);
        if (!$articleState) {
            throw $this->createNotFoundException('Invalid article state.');
        }

        if (!$this->isCsrfTokenValid('change_state' . $newsReport->getId() . $state, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $newsReportRepository->changeState($newsReport->getId(), $articleState);
        $entityManager->flush();
         
        return $this->redirectToRoute('show_articles', ['state' => $state, 'authorId' => $request->getSession()->get('id')], Response::HTTP_SEE_OTHER);
    }

    #[Route('/article/delete/{id}', name: 'delete_report', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, NewsReport $newsReport, EntityManagerInterface $entityManager): Response
    {
        $sessionAuthorId = $request->getSession()->get('id');
        if ($sessionAuthorId === null) {
            return $this->redirectToRoute('app_user_login', [], Response::HTTP_SEE_OTHER);
        }

        if ($newsReport->getAuthor()?->getId() !== (int) $sessionAuthorId) {
            throw $this->createAccessDeniedException('You can only delete your own articles.');
        }

        if ($this->isCsrfTokenValid('delete'.$newsReport->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($newsReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('show_articles', ['state' => 'draft', 'authorId' => $request->getSession()->get('id')], Response::HTTP_SEE_OTHER);
    }

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
            $newsReport->setState(ArticleState::PUBLISHED);
            $newsReport->setPublishedAt($now);
            return;
        }

        $newsReport->setState(ArticleState::DRAFT);

        if ($newsReport->getPublishedAt() === null) {
            $newsReport->setPublishedAt($now);
        }
    }
}
