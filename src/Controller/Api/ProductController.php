<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', name: 'api_product_list', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findBy(
            ['isActive' => true],
            ['id' => 'ASC']
        );

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
            ];
        }

        return $this->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    #[Route('/suggestions', methods: ['GET'])]
    // public function suggestions(Request $request, ProductRepository $repo): JsonResponse
    // {
    //     $q = $request->query->get('q');

    //     if (!$q) {
    //         return $this->json([]);
    //     }

    //     $results = $repo->createQueryBuilder('p')
    //         ->select('p.id', 'p.name')
    //         ->where('p.name LIKE :q')
    //         ->setParameter('q', $q . '%')
    //         ->setMaxResults(5)
    //         ->getQuery()
    //         ->getResult();

    //     return $this->json($results);
    // }

    public function suggestions(
        Request $request,
        ProductRepository $repo,
        CacheInterface $cache
    ): JsonResponse {

        $q = $request->query->get('q');

        if (!$q) {
            return $this->json([]);
        }

        $results = $cache->get('search_' . $q, function (ItemInterface $item) use ($repo, $q) {
            $item->expiresAfter(300); // cache 5 minutes

            return $repo->createQueryBuilder('p')
                ->select('p.id', 'p.name')
                ->where('p.name LIKE :q')
                ->setParameter('q', $q . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        });

        return $this->json($results);
    }
}
