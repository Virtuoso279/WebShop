<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_home')]
    public function profile(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $id = $request->query->get('userId');

        if (!$id) {
            throw new \Exception('User ID is required');
        }

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $session->set('userId', $user->getId());

        return $this->render('profile/profile.html.twig', [
            'userInfo' => $user,
        ]);
    }

    #[Route('/profile/catalog', name: 'catalog')]
    public function catalog(EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        // check user logged
        $id = $session->get('userId');

        if (!$id) {
            throw new \Exception('User ID is required');
        }

        // get list of all products
        $allProducts = $entityManager->getRepository(Product::class)->findAll();

        if (!$allProducts) {
            throw $this->createNotFoundException('Products not found');
        }

        // add session variable cart_products_list
        $cartProducts = $session->get('cart_products_list', []);

        if (empty($cartProducts)) {
            $products = "Empty cart";
        } else {
            $products = $cartProducts;
        }

        // calculate total price
        $cartPrice = 0;
        if ($products !== "Empty cart") {
            foreach ($products as $productId => $quantity) {
                foreach ($allProducts as $itemProduct) {
                    if ($productId == $itemProduct->getId()) {
                        $cartPrice += $quantity * $itemProduct->getPriceProduct();
                        break;
                    }
                }
            }
        }

        return $this->render('profile/catalog.html.twig', [
            'products' => $allProducts,
            'userId' => $id,
            'cart_list' => $products,
            'cart_price' => $cartPrice
        ]);
    }

    #[Route('/profile/orders', name: 'orders')]
    public function orders(EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        // check user logged
        $id = $session->get('userId');

        if (!$id) {
            throw new \Exception('User ID is required');
        }

        // get list of all orders
        $allOrders = $entityManager->getRepository(Order::class)->findBy(['userId' => $id]);

        if (!$allOrders) {
            $allOrders = "Empty list of orders";
        }

        return $this->render('profile/orders.html.twig', [
            'orders_info' => $allOrders,
            'userId' => $id
        ]);
    }
}
