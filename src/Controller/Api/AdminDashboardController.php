<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Security\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/dashboard')]
class AdminDashboardController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        TokenAuthenticator $auth,
        ProductRepository $productRepo,
        OrderRepository $orderRepo
    ): JsonResponse {
        $phone = $auth->getPhoneFromRequest($request);

        // ⚠️ TEMP admin check
        if ($phone !== '9600989314') {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $totalProducts = $productRepo->count([]);
        $totalOrders = $orderRepo->count([]);

        $totalRevenue = 0;
        foreach ($orderRepo->findAll() as $order) {
            $totalRevenue += $order->getTotalAmount();
        }

        return $this->json([
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
