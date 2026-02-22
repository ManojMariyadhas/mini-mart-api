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
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
            'image' => $p->getImage(),
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

        $name = $request->request->get('name');
        $category = $request->request->get('category');
        $price = $request->request->get('price');

        if (!$name || !$category || !$price) {
            return $this->json(['message' => 'Missing fields'], 400);
        }

        $product = new Product();
        $product->setName($name);
        $product->setCategory($category);
        $product->setPrice((int)$price);
        $product->setIsActive(true);


        // Upload Image
        $imageFile = $request->files->get('image');

        if ($imageFile) {

            $fileName = uniqid() . '.webp';

            $uploadPath = $this->getParameter('kernel.project_dir')
                . '/public/uploads/products/';

            $manager = new ImageManager(new Driver());

            $image = $manager->read($imageFile);

            $image->scale(width: 800);

            $image->toWebp(80)->save($uploadPath . $fileName);

            $product->setImage('/uploads/products/' . $fileName);
        }


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


        // Read FormData fields
        $name = $request->request->get('name');
        $category = $request->request->get('category');
        $price = $request->request->get('price');


        if ($name) {
            $product->setName($name);
        }

        if ($category) {
            $product->setCategory($category);
        }

        if ($price) {
            $product->setPrice((int)$price);
        }


        // Image Upload
        $imageFile = $request->files->get('image');

        if ($imageFile) {

            $fileName = uniqid() . '.webp';

            $uploadPath =
                $this->getParameter('kernel.project_dir')
                . '/public/uploads/products/';


            $imageFile->move(
                $uploadPath,
                $fileName
            );


            $product->setImage('/uploads/products/' . $fileName);
        }


        $em->flush();


        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'category' => $product->getCategory(),
            'price' => $product->getPrice(),
            'image' => $product->getImage()
        ]);
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
