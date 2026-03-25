<?php

namespace App\Controller;

use App\Entity\Enums\UserRole;

use App\Entity\Enums\UserState;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\NewsReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route]
final class UserController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    #[Route('/signin', name: 'app_user_login', methods: ['GET', 'POST'])]
    public function login(UserPasswordHasherInterface $passwordHasher,UserRepository $userRepository,Request $request, EntityManagerInterface $entityManager){     
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->remove("role");
        $form->remove("state");
        $form->remove("mail");
        $form->remove("fullname");
        $form->handleRequest($request);
        
        $statusCode = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        if ($form->isSubmitted() && $form->isValid() && $this->isCsrfTokenValid('user' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $plaintextPassword = $user->getPassword();
            $user = $userRepository->findOneBy(["login" => $user->getLogin()]);
            if(!$user){
                $code=Response::HTTP_UNPROCESSABLE_ENTITY;
                $this->addFlash('danger', 'Invalid username');
            }else{
            $isValid = $passwordHasher->isPasswordValid(
                $user,
                $plaintextPassword
            );
            if ($isValid) {
                if($user->getState() == UserState::LOCKED){
                    $code=Response::HTTP_UNPROCESSABLE_ENTITY;
                    $this->addFlash('danger', 'Your account is locked, please contact support');
                }else{
                    $session=$this->requestStack->getSession();
                    $session->set("id", $user->getId());
                    $session->set("login", $user->getLogin());
                    $session->set("role", $user->getRole());
                    $session->set("state", $user->getState());
                    return $this->redirectToRoute('app_my_account', [], Response::HTTP_SEE_OTHER);
                }
            }else{
                $code=Response::HTTP_UNPROCESSABLE_ENTITY;
                $this->addFlash('danger', 'Invalid password');
            }
        }
        }

        $response=$this->render('user/login.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'form' => $form,
            'user' => $user,
        ]);
        $response->setStatusCode($statusCode);
        return $response;
    }

    #[Route('/signup', name: 'app_user_signup', methods: ['GET', 'POST'])]
    public function signup(UserPasswordHasherInterface $passwordHasher, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->remove("role");
        $form->remove("state");
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isCsrfTokenValid('user' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $plaintextPassword = $user->getPassword();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );
            $user->setPassword($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();

            $session = $this->requestStack->getSession();
            $session->set("id", $user->getId());
            $session->set("login", $user->getLogin());
            $session->set("role", $user->getRole());
            $session->set("state", $user->getState());

            return $this->redirectToRoute('app_my_account', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/signout', name: 'app_user_signout', methods: ['GET', 'POST'])]
    public function signout(): Response
    {
        $session=$this->requestStack->getSession();
        $session->remove("login");
        $session->remove("id");
        $session->remove("role");
        $session->remove("state");
        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/myaccount', name: 'app_my_account', methods: ['GET'])]
    public function show(UserRepository $userRepository, NewsReportRepository $newsReportRepository, LoggerInterface $logger): Response
    {
        $session=$this->requestStack->getSession();

        if($session == null || $session->get("login") == null) {
            return $this->redirectToRoute('app_user_signup', [], Response::HTTP_SEE_OTHER);
        }

        $login = $session->get("login");
        $user = $userRepository->findOneBy(["login" => $login]);

        $articlesStats = $newsReportRepository->countAuthorViewsAndArticles($session->get("id"));
        $draftCount = $newsReportRepository->countAuthorDrafts($session->get("id"));

        if($user == null) {
            return $this->redirectToRoute('app_user_signup', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/myaccount.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'user' => $user,
            'total_articles' => $articlesStats['numberOfArticles'],
            'total_views' => $articlesStats['totalViews'],
            'total_drafts' => $draftCount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_my_account', [], Response::HTTP_SEE_OTHER);
        }

        $form->remove("role");
        $form->remove("state");
        $form->remove("login");

        return $this->render('user/edit.html.twig', [
            'is_logged' => $this->requestStack->getSession()->get("login") != null,
            'is_admin' => $this->requestStack->getSession()->get("role") == UserRole::ADMIN,
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}
