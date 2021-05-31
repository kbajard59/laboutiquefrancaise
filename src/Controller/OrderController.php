<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(entityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="order")
     */
    public function index(Request $request,Cart $cart): Response
    {
        if(!$this->getUser()->getAddresses()->getValues()){
            // Si il n'y a pas d'adresse alors redirection vers page de création d'adresse
            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class,null, [
            'user' =>$this->getUser()
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            dd($form->getData());
        }

        return $this->render('order/index.html.twig', [
            'form' =>$form->createView(),
            'cart' => $cart->getFull()
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     */
    public function add(Request $request,Cart $cart): Response
    {
        $form = $this->createForm(OrderType::class,null, [
            'user' =>$this->getUser()
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $date = new \DateTime();
            $carriers = $form->get('carriers')->getData();
            $delivery = $form->get('addresses')->getData();
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();
            if($delivery->getCompany()){
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getCountry();

            //enregistrer mes commandes Order()
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setIsPaid(0);
            $this->entityManager->persist($order);


            $YOUR_DOMAIN = 'http://127.0.0.1:8000';
            $product_for_stripe=[];
            //enregistrer mes produits OrderDetails()
            foreach ($cart->getFull() as $p){
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($p['product']->getName());
                $orderDetails->setQuantity($p['quantity']);
                $orderDetails->setPrice($p['product']->getPrice());
                $orderDetails->settotal($p['product']->getPrice() * $p['quantity']);
                $this->entityManager->persist($orderDetails);

                $product_for_stripe[] = [
                    'price_data' => [ // à modifier pour adapter
                        'currency' => 'eur',
                        'unit_amount' => $p['product']->getPrice(),
                        'product_data' => [
                            'name' => $p['product']->getName(),
                            'images' => [$YOUR_DOMAIN."/uploads/".$p['product']->getIllustration()],
                        ],
                    ],
                    'quantity' => $p['quantity'],
                ];

            }
dd($product_for_stripe);
            $this->entityManager->flush();

            Stripe::setApiKey('sk_test_51IxHydJxv3iM3SoR5YYpVJTVQoCd6pngw2SQszcDkTrRtwgoVTYUeDGFWE9wRCov2UaYi6gAjdjaEciVGWJWClX800FxmQU5ih');



            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [ // à modifier pour adapter
                        'currency' => 'usd',
                        'unit_amount' => 2000,
                        'product_data' => [
                            'name' => 'Stubborn Attachments',
                            'images' => ["https://i.imgur.com/EHyR2nP.png"],
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $YOUR_DOMAIN . '/success.html',
                'cancel_url' => $YOUR_DOMAIN . '/cancel.html',

            ]);
            return $this->render('order/add.html.twig', [
                'cart' => $cart->getFull(),
                'carrier' =>$carriers,
                'delivery' =>$delivery_content
            ]);
        }

        return $this->redirectToRoute('cart');
    }
}
