<?php

namespace App\Controller\Api;

use App\Repository\OrderRepository;
use App\Security\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        TokenAuthenticator $auth,
        OrderRepository $orderRepository
    ): JsonResponse {
        $phone = $auth->getPhoneFromRequest($request);

        if (!$phone) {
            return $auth->unauthorized();
        }

        // 🔐 ADMIN CHECK
        if ($phone !== '9600989314') {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $orders = $orderRepository->findBy([], ['id' => 'DESC']);

        $data = [];

        foreach ($orders as $order) {
            $items = [];

            foreach ($order->getItems() as $item) {
                $items[] = [
                    'product' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                ];
            }

            $data[] = [
                'id' => $order->getId(),
                'customerName' => $order->getCustomerName(),
                'phone' => $order->getPhone(),
                'totalAmount' => $order->getTotalAmount(),
                'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i'),
                'items' => $items,
            ];
        }

        return $this->json($data);
    }
}
