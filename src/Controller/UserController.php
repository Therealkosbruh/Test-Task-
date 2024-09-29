<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    // USER CREATION
    #[Route('/users', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        if (!isset($data['login']) || !isset($data['password'])) {
            return $this->json(['error' => 'Login and password are required'], 400);
        }

        $password = $data['password'];

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{6,}$/', $password)) {
            return $this->json(['error' => 'Password must be at least 6 characters long, contain at least one lowercase letter, one uppercase letter, one digit and one special character'], 400);
        }

        $user = new User();
        $user->setLogin($data['login']);
        $user->setPassword($password);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User created successfully'], 201);
    }

    // GET INFO ABOUT USER BY ID OR LOGIN
    #[Route('/users/{id}', methods: ['GET'])]
    #[Route('/users/login/{login}', methods: ['GET'])]
    public function getUserInfo(Request $request, EntityManagerInterface $entityManager, $id = null, $login = null): JsonResponse
    {
        if ($id) {
            $user = $entityManager->getRepository(User::class)->find($id);
        } elseif ($login) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
        } else {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'password' => $user->getPassword(),
        ]);
    }

    // DELETE USER BY ID OR LOGIN
    #[Route('/users/{id}', methods: ['DELETE'])]
    #[Route('/users/del/{login}', methods: ['DELETE'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, $id = null, $login = null): JsonResponse
    {
        if ($id) {
            $user = $entityManager->getRepository(User::class)->find($id);
        } elseif ($login) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
        } else {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'User deleted successfully'], 200);
    }

    // CHANGE USER DATA
    #[Route('/users/{id}/update', methods: ['PATCH'])]
    #[Route('/users/update/{login}', methods: ['PATCH'])]
    public function partialUpdateUser(Request $request, EntityManagerInterface $entityManager, $id = null, $login = null): JsonResponse
    {
        if ($id) {
            $user = $entityManager->getRepository(User::class)->find($id);
        } elseif ($login) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
        } else {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        if (isset($data['login'])) {
            $user->setLogin($data['login']);
        }

        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User updated successfully'], 200);
    }

    // USER LOGIN    
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        $login = $data['login'];
        $password = $data['password'];

        $user = $entityManager->getRepository(User::class)->findOneBy(['login' => $login]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if ($user->getPassword() !== $password) {
            return $this->json(['error' => 'Invalid password'], 401);
        }
        return $this->json(['message' => 'Login successful'], 200);
    }
}