<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/nous-contacter", name="contact")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->addFlash('notice','Merci de nous avoir contacter. Notre équipe vous répondra dans les plus brefs délais !');
            $content = $form->get('content')->getData();
            //Envoi du mail
            $mail = new Mail();
            $mail->send('kevin.bajard@hotmail.com','La Boutique Française','Demande de contact',$content);
        }

        return $this->render('contact/index.html.twig',[
            'form' => $form->createView()
        ]);
    }
}
