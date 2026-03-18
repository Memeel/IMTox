<?php

namespace App\Controller;

use App\Entity\ReportTag;
use App\Form\ReportTagType;
use App\Repository\ReportTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/report/tag')]
final class ReportTagController extends AbstractController
{
    #[Route(name: 'app_report_tag_index', methods: ['GET'])]
    public function index(ReportTagRepository $reportTagRepository): Response
    {
        return $this->render('report_tag/index.html.twig', [
            'report_tags' => $reportTagRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_report_tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reportTag = new ReportTag();
        $form = $this->createForm(ReportTagType::class, $reportTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reportTag);
            $entityManager->flush();

            return $this->redirectToRoute('app_report_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('report_tag/new.html.twig', [
            'report_tag' => $reportTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_report_tag_show', methods: ['GET'])]
    public function show(ReportTag $reportTag): Response
    {
        return $this->render('report_tag/show.html.twig', [
            'report_tag' => $reportTag,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_report_tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReportTag $reportTag, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReportTagType::class, $reportTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_report_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('report_tag/edit.html.twig', [
            'report_tag' => $reportTag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_report_tag_delete', methods: ['POST'])]
    public function delete(Request $request, ReportTag $reportTag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reportTag->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reportTag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_report_tag_index', [], Response::HTTP_SEE_OTHER);
    }
}
