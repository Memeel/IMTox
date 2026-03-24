<?php

namespace App\Controller;

use App\Entity\ReportTag;
use App\Entity\User;
use App\Form\ReportTagType;
use App\Repository\ReportTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Entity\Enums\UserState;

final class AdminController extends AbstractController
{

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function listUsers(Request $request, UserRepository $userRepo)
    {
        $search = $request->query->get('search');
        $role   = $request->query->get('role');
        $state  = $request->query->get('state');

        $qb = $userRepo->createQueryBuilder('u');

        // Exclure les deleted
        $qb->andWhere('u.state != :deleted')
        ->setParameter('deleted', UserState::DELETED);

        if ($search) {
            $qb->andWhere('u.fullname LIKE :search OR u.mail LIKE :search')
            ->setParameter('search', '%'.$search.'%');
        }

        if ($role) {
            $qb->andWhere('u.role = :role')
            ->setParameter('role', $role);
        }

        if ($state) {
            $qb->andWhere('u.state = :state')
            ->setParameter('state', $state);
        }

        $users = $qb->getQuery()->getResult();
        $active = $userRepo->getNumberActiveUsers($users);
        $locked = $userRepo->getNumberLockedUsers($users);

        return $this->render('admin/users.html.twig', [
            'users'  => $users,
            'active' => $active,
            'locked' => $locked,
        ]);
    }
    #[Route('/admin/user/delete/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {

            $user->setState(UserState::DELETED);
            $user->setMail(null);
            $user->setLogin(null);
            $user->setPassword(null);
            $user->setRole(null);

            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_users', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/admin/user/lock/{id}', name: 'app_admin_user_lock', methods: ['POST'])]
    public function lockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('lock'.$user->getId(), $request->request->get('_token'))) {
            $user->setState(UserState::LOCKED); // <- enum
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_users');
    }
    #[Route('/admin/user/unlock/{id}', name: 'app_admin_user_unlock', methods: ['POST'])]
    public function unlockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('unlock'.$user->getId(), $request->request->get('_token'))) {
            $user->setState(UserState::ACTIVE); // <- enum
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_users');
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
    public function deleteTag(Request $request, ReportTag $tag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
    }
}
