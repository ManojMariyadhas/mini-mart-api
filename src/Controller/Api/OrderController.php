<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_create_order', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON'], 400);
        }

        // Basic validation
        if (
            empty($data['customerName']) ||
            empty($data['phone']) ||
            empty($data['address']) ||
            empty($data['items'])
        ) {
            return $this->json(['message' => 'Missing required fields'], 400);
        }

        $order = new Order();
        $order->setCustomerName($data['customerName']);
        $order->setPhone($data['phone']);
        $order->setAddress($data['address']);

        $totalAmount = 0;

        foreach ($data['items'] as $itemData) {
            if (
                empty($itemData['productId']) ||
                empty($itemData['quantity'])
            ) {
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
}
