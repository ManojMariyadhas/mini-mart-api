<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepo
    ): JsonResponse {

        $search = $request->query->get('search', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $offset = ($page - 1) * $limit;

        $qb = $productRepo->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.name LIKE :q OR p.category LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        $total = (clone $qb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $products = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'category' => $p->getCategory(),
            'price' => $p->getPrice(),
        ], $products);

        return $this->json([
            'data' => $data,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (
            empty($data['name']) ||
            empty($data['category']) ||
            empty($data['price'])
        ) {
            return $this->json(['message' => 'Missing fields'], 400);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setCategory($data['category']);
        $product->setPrice((int) $data['price']);
        $product->setIsActive(true);

        $em->persist($product);
        $em->flush();

        return $this->json($product, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ProductRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {

        $product = $repo->find($id);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['category'])) {
            $product->setCategory($data['category']);
        }
        if (isset($data['price'])) {
            $product->setPrice((int) $data['price']);
        }

        $em->flush();

        return $this->json($product);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        ProductRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {

        $product = $repo->find($id);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $em->remove($product);
        $em->flush();

        return $this->json(['status' => true]);
    }
}
