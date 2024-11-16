<?php

namespace App\Controller;

use App\Class\Mail;
use App\Entity\User;
use App\Entity\LoginCode;
use App\Form\RegisterUserType;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

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

    #[Route('/auth/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, JWTManager $JWTManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Recherche l'utilisateur dans la base de données
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // $token = $JWTManager->create($user);

        return new JsonResponse(
            [
                    'message' => 'Login successful',
                    // 'token' => $token
                  ],
        
            JsonResponse::HTTP_OK,
            [
                        'Access-Control-Allow-Origin' => '*', 
                        'Access-Control-Allow-Credentials' => 'true'
                    ]
        );
    }

    #[Route('/auth/send-code', name: 'send_code', methods: ['POST'])]
    public function sendCode(Request $request, MailerInterface $mailer, LoggerInterface $logger, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifiez si l'email est fourni
        if (empty($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $emailAddress = $data['email'];

        // Génération d'un code à 6 chiffres
        $code = random_int(100000, 999999);

        // Vérifier si l'utilisateur existe, sinon le créer
        $user = $em->getRepository(User::class)->findOneBy(['email' => $emailAddress]);
        if (!$user) {
            $user = new User();
            $user->setEmail($emailAddress);
            $em->persist($user);
            $em->flush();
    }

        $token = $jwtManager->create($user);

        $session = $request->getSession();

        // Stocker le code et l'email dans la session
        $session->set('verification_code', $code);
        $session->set('email', $emailAddress);

        // A Effacer, uniquement pour les tests de login 
        $logger->info("Code de vérification généré : $code pour l'email $emailAddress");
        $logger->debug('Contenu de la session send_code: ', [
            'verification_code' => $session->get('verification_code'),
            'email' => $session->get('email'),
        ]);

        // Envoyer l'email avec le code
        // $email = new Mail();
        // $vars = [
        //     'code' => $code
        // ];
        // $email->send($emailAddress,'John Doe',  "Test de mail d'authentification", "authCode.html", $vars );

        return new JsonResponse(['message' => 'Code sent successfully', 
                                 'token' => $token
                                ]);
    }

    #[Route('/auth/verify-code', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(Request $request, EntityManagerInterface $em, LoggerInterface $logger, JWTEncoderInterface $jwtEncoder): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $submittedCode = $data['code'];
        $submittedEmail = $data['email'];
    
        // $session = $request->getSession();

        // $logger->debug('Cookies de la session :', ['session_id' => $session->getId()]);

        // // Récupérer le code et l'email depuis la session
        // $storedCode = $session->get('verification_code');
        // $storedEmail = $session->get('email');

        // $logger->debug('Contenu de la session verify_code : ', [
        //     'verification_code' => $session->get('verification_code'),
        //     'email' => $session->get('email'),
        // ]);
        
        // // Vérifier que les valeurs correspondent
        // if ($storedCode && $storedEmail && 
        //     $storedCode == $submittedCode && 
        //     $storedEmail == $submittedEmail) {
    
        //     // Récupérer l'utilisateur depuis la base de données
        //     $user = $em->getRepository(User::class)->findOneByEmail($submittedEmail);
    
        //     if (!$user) {
        //         return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        //     }
    
        //     // Authentification réussie, connecter l'utilisateur dans la session
        //     $session->set('user_id', $user->getId());
        //     $session->set('user_email', $user->getEmail());
    
        //     // Nettoyer la session du code
        //     $session->remove('verification_code');
        //     $session->remove('email');
    
        //     // Retourner une réponse de succès sans token
        //     return new JsonResponse(['message' => 'Code verified successfully'], JsonResponse::HTTP_OK);
        
        // Récupérer le JWT depuis l'en-tête Authorization
        $token = $request->headers->get('Authorization');

        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        } else {
            return new JsonResponse(['error' => 'Token missing or invalid'], 400);
        }
    
        try {
            $decoded = $jwtEncoder->decode($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 400);
        }
    
        // Vérifier si l'utilisateur existe
        $user = $em->getRepository(User::class)->findOneBy(['email' => $decoded['email']]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
    
        // Comparer le code
        if ($decoded['email'] !== $submittedEmail || $submittedCode !== $user->getVerificationCode()) {
            return new JsonResponse(['error' => 'Invalid code or email'], 400);
        }
    
        $logger->info("Code vérifié avec succès pour l'email: $submittedEmail");
    
        return new JsonResponse(['message' => 'Verification successful']);
        }

}
