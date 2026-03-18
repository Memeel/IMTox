<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/admin/tags', name: 'app_admin_tags')]
    public function tags(): Response
    {
        return $this->render('admin/users.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
