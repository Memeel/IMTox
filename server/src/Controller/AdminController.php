<?php

/**
 * @author Julien Orsoni <julien.orsoni@imt-atlantique.net>
 * @author Cyprien Gingelwein <cyprien.gingelwein@imt-atlantique.net>
 */

namespace App\Controller;

use App\Entity\Enums\UserRole;
use App\Entity\ReportTag;
use App\Entity\User;
use App\Form\ReportTagType;
use App\Repository\ReportTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Entity\Enums\UserState;

final class AdminController extends AbstractController
{
    /**
     * AdminController
     *
     * Handles admin-only pages and actions: user management and tag management.
     * Access is gated with a session role check against UserRole::ADMIN.
     */
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        // Redirect to the users list as default admin landing page
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function listUsers(Request $request, UserRepository $userRepo)
    {
        // Only allow admins to access this route
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Read filter query parameters (optional)
        $search = $request->query->get('search');
        $role   = $request->query->get('role');
        $state  = $request->query->get('state');

        $qb = $userRepo->createQueryBuilder('u');

        // Exclude users marked as deleted
        $qb->andWhere('u.state != :deleted')
           ->setParameter('deleted', UserState::DELETED);

        if ($search) {
            // Filter by fullname or mail containing the search term
            $qb->andWhere('u.fullname LIKE :search OR u.mail LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($role) {
            // Filter by role value
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }

        if ($state) {
            // Filter by account state
            $qb->andWhere('u.state = :state')
               ->setParameter('state', $state);
        }

        $users = $qb->getQuery()->getResult();
        $active = $userRepo->getNumberActiveUsers($users);
        $locked = $userRepo->getNumberLockedUsers($users);

        // Render the users list template with computed stats
        return $this->render('admin/users.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get('login') != null,
            'is_admin'  => $this->requestStack->getSession()->get('role') == UserRole::ADMIN,
            'users'     => $users,
            'active'    => $active,
            'locked'    => $locked,
        ]);
    }
    #[Route('/admin/user/delete/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if($user->getId() != $this->requestStack->getSession()->get("id") && $this->requestStack->getSession()->get("role") != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Validate CSRF token before performing destructive action
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Soft-delete: clear sensitive fields and mark as deleted
            $user->setState(UserState::DELETED);
            $user->setLogin("deleted" . strval($user->getId()));
            $user->setMail("deleted" . strval($user->getId()));
            $user->setPassword(null);
            $user->setRole(null);

            $entityManager->flush();
        }

        if($user->getId() == $this->requestStack->getSession()->get("id")) {
            $this->requestStack->getSession()->clear();
        }

        return $this->redirectToRoute('app_admin_users', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/admin/user/lock/{id}', name: 'app_admin_user_lock', methods: ['POST'])]
    public function lockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        // Admin guard
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Protect action with CSRF token
        if ($this->isCsrfTokenValid('lock' . $user->getId(), $request->request->get('_token'))) {
            $user->setState(UserState::LOCKED);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_users');
    }
    #[Route('/admin/user/unlock/{id}', name: 'app_admin_user_unlock', methods: ['POST'])]
    public function unlockUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        if ($this->isCsrfTokenValid('unlock' . $user->getId(), $request->request->get('_token'))) {
            $user->setState(UserState::ACTIVE);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/tags', name: 'app_admin_tag_index')]
    public function tags(ReportTagRepository $reportTagRepository): Response
    {
        // Only admins may manage tags
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        return $this->render('admin/tag_index.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get('login') != null,
            'is_admin'  => $this->requestStack->getSession()->get('role') == UserRole::ADMIN,
            'tags'      => $reportTagRepository->findAll(),
        ]);
    }

    #[Route('/admin/tags/add', name: 'app_admin_tag_add', methods: ['GET', 'POST'])]
    public function addTag(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Build and handle the tag form
        $tag = new ReportTag();
        $form = $this->createForm(ReportTagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tag);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tag_add.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get('login') != null,
            'is_admin'  => $this->requestStack->getSession()->get('role') == UserRole::ADMIN,
            'tag'       => $tag,
            'form'      => $form,
        ]);
    }

    #[Route('/admin/tags/{id}/edit', name: 'app_admin_tag_edit', methods: ['GET', 'POST'])]
    public function editTag(Request $request, ReportTag $reportTag, EntityManagerInterface $entityManager): Response
    {
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Edit existing tag via form
        $form = $this->createForm(ReportTagType::class, $reportTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tag_edit.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get('login') != null,
            'is_admin'  => $this->requestStack->getSession()->get('role') == UserRole::ADMIN,
            'tag'       => $reportTag,
            'form'      => $form,
        ]);
    }

    #[Route('/admin/tags/{id}', name: 'app_admin_tag_delete', methods: ['POST'])]
    public function deleteTag(Request $request, ReportTag $tag, EntityManagerInterface $entityManager): Response
    {
        if ($this->requestStack->getSession()->get('role') != UserRole::ADMIN) {
            return $this->redirectToRoute('app');
        }

        // Validate CSRF and remove tag
        if ($this->isCsrfTokenValid('delete' . $tag->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tag);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_tag_index', [], Response::HTTP_SEE_OTHER);
    }
}
