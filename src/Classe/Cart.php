<?php

namespace App\Classe;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{
    private $session;
    private $entityManager;

    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->entityManager = $entityManager;
    }

    public function add($id)
    {
        $cart = $this->session->get('cart',[]); // Le deuxième param permet de retourner un tableau

        if(!empty($cart[$id])){ // Si le produit ajouté n'est pas déjà présent dans le panier
            $cart[$id]++; // Pour ajouter 1 à la quantité
        }else{
            $cart[$id] = 1; // Sinon on l'initialiser à 1
        }

        $this->session->set('cart',$cart);
    }

    public function get()
    {
        return $this->session->get('cart');
    }

    public function remove()
    {
        return $this->session->remove('cart');
    }

    public function delete($id)
    {
        $cart = $this->session->get('cart',[]);

        unset($cart[$id]);

        return $this->session->set('cart',$cart);
    }

    public function decrease($id){

        $cart = $this->session->get('cart',[]);

        if($cart[$id] > 1){ //Verifier si la quantité de notre produit n'est pas = a 1
            $cart[$id]--;
        }else{
            unset($cart[$id]);
        }
        return $this->session->set('cart',$cart);
    }

    public function getFull(){ //Pour récupérer entièrement le panier

        $cartComplete = [];

        if($this->get()){
            foreach($this->get() as $id => $quantity){
                $product_object = $this->entityManager->getRepository(Product::class)->findOneById($id); //recup de l'objet
                if($product_object){ //si l'objet existe on l'ajoute
                    $cartComplete[]=[
                        'product' => $product_object,
                        'quantity'=> $quantity
                    ];
                }else{ // sinon on le supprime
                    $this->delete($id);
                }
            }
        }
        return $cartComplete;
    }
}