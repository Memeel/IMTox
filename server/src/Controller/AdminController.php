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
use App\Repository\UserRepository;

final class AdminController extends AbstractController
{

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
{

    $users = $userRepository->findAll();

    // Compter les utilisateurs actifs et verrouillés
    $active = $userRepository->getNumberLockedUsers($users);
    $locked = $userRepository->getNumberLockedUsers($users);

    // Rendre la vue avec les variables nécessaires
    return $this->render('admin/users.html.twig', [
        'users' => $users,
        'active'=> $active,
        'locked' => $locked,
    ]);
}

    #[Route('/admin/tags', name: 'app_admin_tag_index')]
    public function tags(ReportTagRepository $reportTagRepository): Response
    {
        return $this->render('admin/tag_index.html.twig', [
            'tags' => $reportTagRepository->findAll(),
        ]);
    }

    #[Route('/admin/tags/add', name: 'app_admin_tag_add', methods: ['GET', 'POST'])]
    public function addTag(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tag = new ReportTag();
        $form = $this->createForm(ReportTagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tag);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tag_add.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    #[Route('/admin/tags/{id}/edit', name: 'app_admin_tag_edit', methods: ['GET', 'POST'])]
    public function editTag(Request $request, ReportTag $reportTag, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReportTagType::class, $reportTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tag_edit.html.twig', [
            'tag' => $reportTag,
            'form' => $form,
        ]);
    }

    #[Route('/admin/tags/{id}', name: 'app_admin_tag_delete', methods: ['POST'])]
    public function delete(Request $request, ReportTag $tag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
    }
}
