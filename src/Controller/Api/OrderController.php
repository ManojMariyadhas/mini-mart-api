<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_create_order', methods: ['POST'])]
    public function create(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        ProductRepository $productRepository
    ): JsonResponse {

        // 🔐 Get logged-in user from JWT
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $phone = $user->getPhone();

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['message' => 'Invalid JSON'], 400);
        }

        if (
            empty($data['customerName']) ||
            empty($data['address']) ||
            empty($data['items'])
        ) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $order = new Order();
        $order->setCustomerName($data['customerName']);
        $order->setPhone($phone);
        $order->setAddress($data['address']);

        $totalAmount = 0;

        foreach ($data['items'] as $itemData) {

            if (empty($itemData['productId']) || empty($itemData['quantity'])) {
                return $this->json(['message' => 'Invalid item data'], 400);
            }

            $product = $productRepository->find($itemData['productId']);

            if (!$product) {
                return $this->json(['message' => 'Product not found'], 404);
            }

            $quantity = (int) $itemData['quantity'];

            if ($quantity <= 0) {
                return $this->json(['message' => 'Invalid quantity'], 400);
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());
            $orderItem->setParentOrder($order);

            $order->addItem($orderItem);
            $em->persist($orderItem);

            $totalAmount += $product->getPrice() * $quantity;
        }

        $order->setTotalAmount($totalAmount);

        $em->persist($order);
        $em->flush();

        return $this->json([
            'status' => true,
            'orderId' => $order->getId(),
            'totalAmount' => $totalAmount,
        ], 201);
    }

    #[Route('/go', name: 'api_get_orders', methods: ['GET'])]
    public function index(
        Security $security,
        OrderRepository $orderRepository
    ): JsonResponse {

        $user = $security->getUser();

        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $phone = $user->getPhone();

        $orders = $orderRepository->findBy(
            ['phone' => $phone],
            ['id' => 'DESC']
        );

        $response = [];

        foreach ($orders as $order) {

            $items = [];

            foreach ($order->getItems() as $item) {
                $items[] = [
                    'product' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                ];
            }

            $response[] = [
                'id' => $order->getId(),
                'totalAmount' => $order->getTotalAmount(),
                'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i'),
                'items' => $items,
            ];
        }

        return $this->json($response);
    }
}
