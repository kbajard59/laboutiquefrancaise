<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderSuccessController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_validate")
     */
    public function index($stripeSessionId, Cart $cart): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if(!$order || $order->getUser() != $this->getUser()){ // redirection si la commande n'existe pas ou l'utilisateur n'est pas celui qui est connecter
            return $this->redirectToRoute('home');
        }

        //Modif du status de paiement de la commande
        if(!$order->getIsPaid()){

            $order->setIsPaid(1);
            $this->entityManager->flush();
            $cart->remove();
        }

        return $this->render('order_success/index.html.twig',[
            'order'=>$order
        ]);
    }
}
