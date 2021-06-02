<?php

namespace App\Controller;


use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session")
     */
    public function index(EntityManagerInterface $entityManager, $reference): Response
    {
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        $product_for_stripe=[];

        $order = $entityManager->getRepository(Order::class)->findOneByReference($reference);

        if(!$order){ // Par sécurité si la commande n'existe pas
            new JsonResponse([
                'error' => 'order'
            ]);
        }
        foreach ($order->getOrderDetails()->getValues() as $p){
            $product_object = $entityManager->getRepository(Product::class)->findOneByName($p->getProduct());

            // Pour les produits
            $product_for_stripe[] = [
                'price_data' => [ // à modifier pour adapter
                    'currency' => 'eur',
                    'unit_amount' => $p->getPrice(),
                    'product_data' => [
                        'name' => $p->getProduct(),
                        'images' => [$YOUR_DOMAIN."/uploads/".$product_object->getIllustration()],
                    ],
                ],
                'quantity' => $p->getQuantity(),
            ];
        }

        //Pour la livraison
        $product_for_stripe[] = [
            'price_data' => [ // à modifier pour adapter
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN],
                ],
            ],
            'quantity' => 1,
        ];

        Stripe::setApiKey('sk_test_51IxHydJxv3iM3SoR5YYpVJTVQoCd6pngw2SQszcDkTrRtwgoVTYUeDGFWE9wRCov2UaYi6gAjdjaEciVGWJWClX800FxmQU5ih');

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(), // permet de renseigner directement l'email de l'utilisateur
            'payment_method_types' => ['card'],
            'line_items' => [
                $product_for_stripe
            ],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeSessionId($checkout_session->id);
        $entityManager->flush();

        $response = new JsonResponse([
            'id' => $checkout_session->id
        ]);
        return $response;
    }
}
