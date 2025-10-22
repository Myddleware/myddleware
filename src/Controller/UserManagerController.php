<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManagerController extends AbstractController
{
    #[Route('/rule/user_manager', name: 'user_manager')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('UserManager/list.html.twig', [
            'users' => $users,
            'currentUser' => $this->getUser(),
        ]);
    }
    #[Route('/rule/user_manager/{id}/edit', name: 'user_manager_edit', methods: ['GET'])]
    public function edit(UserRepository $userRepository, int $id, TranslatorInterface $translator): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('user_manager.edit.danger', $translator->trans('user.not_found'));
            return $this->redirectToRoute('user_manager');
        }

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_manager_update', ['id' => $user->getId()]),
            'method' => 'POST',
            'current_user' => $this->getUser(),
        ]);

        return $this->render('UserManager/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rule/user_manager/{id}/update', name: 'user_manager_update', methods: ['POST'])]
    public function update(Request $request, User $user, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('user_manager.edit.success', $translator->trans('success_update_user'));
            return $this->redirectToRoute('user_manager');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('user_manager.edit.danger', $translator->trans('form.invalid'));
        }

        return $this->render('UserManager/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rule/user_manager/new', name: 'user_manager_create')]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'include_password' => true,
            'current_user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUsernameCanonical(strtolower($user->getUsername()));
            $user->setEmailCanonical(strtolower($user->getEmail()));
            $user->setEnabled(true);

            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('user_manager.create.success', $translator->trans('success_create_user'));
            return $this->redirectToRoute('user_manager');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('user_manager.create.danger', $translator->trans('form.invalid'));
        }

        return $this->render('UserManager/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rule/user/{id}/delete', name: 'user_manager_delete', methods: ['GET'])]
    public function delete(User $user, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        try {
            $em->remove($user);
            $em->flush();

            $this->addFlash('user_manager.delete.success', $translator->trans('success_deleted_user'));
        } catch (\Throwable $e) {
            $this->addFlash('user_manager.delete.danger', $translator->trans('failed_deleted_user'));
        }
        return $this->redirectToRoute('user_manager');
    }
}