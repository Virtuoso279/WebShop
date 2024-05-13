<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/add_cart_product/{id}', name: 'cart_products')]
    public function add_cart_product(int $id, SessionInterface $session): Response
    {
        $cartProducts = $session->get('cart_products_list');

        if ($cartProducts === null) {
            $session->set('cart_products_list', []);
            $cartProducts = $session->get('cart_products_list');
        }

        if (array_key_exists($id, $cartProducts)) {
            $cartProducts[$id]++;
        } else {
            $cartProducts[$id] = 1;
        }

        $session->set('cart_products_list', $cartProducts);

        return $this->redirectToRoute('catalog');
    }

    #[Route('/remove_cart_product/{id}', name: 'cart_products_remove')]
    public function remove_cart_product(int $id, SessionInterface $session): Response
    {
        $cartProducts = $session->get('cart_products_list');

        if (array_key_exists($id, $cartProducts)) {
            if ($cartProducts[$id] == 1) {
                unset($cartProducts[$id]);
            } else {
                $cartProducts[$id]--;
            }
        }

        $session->set('cart_products_list', $cartProducts);

        return $this->redirectToRoute('catalog');
    }

    #[Route('/make_order', name: 'make_order')]
    public function make_order(ProductRepository $productRepository, SessionInterface $session, Request $request, EntityManagerInterface $entityManager): Response
    {
        $cartProducts = $session->get('cart_products_list');
        $userId = $session->get('userId');
        $user = $entityManager->getRepository(User::class)->find($userId);

        $totalPrice = $request->query->get('totalPrice');

        if (!$totalPrice) {
            throw new \Exception('Total price of cart is required');
        }

        if (!empty($cartProducts)) {
            $productIds = array_keys($cartProducts);
            $listProducts = $productRepository->findBy(['id' => $productIds]);
        } else {
            throw $this->createNotFoundException('Products in cart not found');
        }   

        $order = new Order();
        $order->setUserId($user);
        $order->setTotalPrice($totalPrice);

        $entityManager->persist($order);

        foreach ($listProducts as $product) {
            $orderProduct = new OrderProduct();
            $orderProduct->setProductId($product);
            $orderProduct->setOrderId($order);
            $orderProduct->setAmount($cartProducts[$product->getId()]);
            $orderProduct->setLastProductPrice($product->getPriceProduct());
            $orderProduct->setTotalPrice($cartProducts[$product->getId()] * $product->getPriceProduct());

            $entityManager->persist($orderProduct);
        }

        $entityManager->flush();
        
        $session->remove('cart_products_list');
        return $this->redirectToRoute('orders');
    }
}
