<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    private $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Récupérer les données JSON envoyées par React
        $data = json_decode($request->getContent(), true);
        
        // Créer un nouvel utilisateur et lier les données
        $user = new User();
        $form = $this->createForm(RegisterUserType::class, $user);

        $form->submit($data); 
    
        if ($form->isSubmitted() && $form->isValid()) {

            $user->setRoles(['ROLE_USER']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            return new JsonResponse(['status' => 'User created successfully'], 201);
        } 
    
        return new JsonResponse( ['error' => 'Invalid data',
                                       'errors' => (string) $form->getErrors(true, false)

                                        ], 400);
                                            
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Recherche l'utilisateur dans la base de données
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['message' => 'Login successful'], JsonResponse::HTTP_OK);
    }
}
