<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/user')]
class UserController extends AbstractController
{
    private $manager;

    private $user;

    public function __construct(EntityManagerInterface $manager, UserRepository $user)
    {
         $this->manager=$manager;

         $this->user=$user;
    }
    


    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['POST'])]
   
    public function new(Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtTokenManager): Response
    {

        $content = $request->getContent();
        $data = json_decode($content, true);

        if ($data === null) {
            // Si la conversion JSON échoue, essayez de décoder les données du formulaire
            $data = $request->request->all();
        }
        
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
    
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Générer le token JWT après la création de l'utilisateur
            $token = $jwtTokenManager->create($user);
    
            // Vous pouvez inclure le token dans la réponse si nécessaire
            $responseData = [
                'status' => true,
                'message' => 'L\'utilisateur créé avec succès',
                'token' => $token,
            ];
    
            // Utilisez JsonResponse pour renvoyer la réponse au format JSON
            return new JsonResponse($responseData);
        }
    
        // Si le formulaire n'est pas valide, continuez comme avant
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
}
 
    

         





    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
