<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entity){
        $this->entityManager=$entity;
    }
    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if($this->getUser()){
            return $this->redirectToRoute('home');
        }

        if($request->get('email')){
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));
            if($user){
                // Etape 1 enregistrer en BDD la demande de reset_password
                $reset_password= new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreateAt(new \DateTime());
                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                // Etape 2 envoyer un mail à l'utilisateur avec un lien lui permettant de mettre à jour son MDP
                $url = $this->generateUrl('edit_password',[
                    'token' => $reset_password->getToken()
                ]);
                $content ='Bonjour '.$user->getFirstName().',<br/><br/>Vous avez demandé à réinitialiser votre mot de passe sur le site La Boutique Française.<br/><br/>
Merci de bien vouloir cliquer sur le lien suivant pour <a href="'.$url.'">mettre à jour votre mot de passe</a>.<br/><br/>
Le lien sera disponible pendant une durée de 30 minutes.';

                $mail = new Mail();
                $mail->send($user->getEmail(),$user->getFirstName().' '.$user->getLastName(),'Réinitialiser votre mot de pase sur La Boutique Française'
                    ,$content);
                $this->addFlash('notice',"Un email pour réinitialiser votre mot de passe vous a été envoyé. ");
            }else{
                $this->addFlash('notice',"Cette adresse email est inconnue.");
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mot-de-passe/{token}", name="edit_password")
     */
    public function modif(UserPasswordEncoderInterface $encoder,Request $request,$token): Response
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if(!$reset_password){
            return $this->redirectToRoute('reset_password');
        }
        //Vérifier si le createdAt = now - 30min
        $now = new \DateTime();
        if($now > $reset_password->getCreateAt()->modify('+ 30 minute')){
            $this->addFlash('notice','Votre demande de mot de passe a expirée. Merci de la renouvelée.');
            return $this->redirectToRoute('reset_password');
        }

        // rendre une vue avec mot de passe et confirmez votre mot de passe.
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $new_pw = $form->get('new_password')->getData();
            // Encodage des mdp
            $password = $encoder->encodePassword($reset_password->getUser(),$new_pw);
            $reset_password->getUser()->setPassword($password);

            // flush en bdd
            $this->entityManager->flush();

            // redirection vers la page de connexion
            $this->addFlash('notice',"Votre mot de passe a bien été mis à jour.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig',[
            'form' =>$form->createView()
        ]);
    }
}
