<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\CategoryRepository;
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

            $qb->leftJoin('p.category', 'c');

            $qb->andWhere(
                'p.name LIKE :q OR c.name LIKE :q'
            )
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

            'category' => [
                'id' => $p->getCategory()->getId(),
                'name' => $p->getCategory()->getName()
            ],

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
        EntityManagerInterface $em,
        CategoryRepository $categoryRepo
    ): JsonResponse {

        $name = $request->request->get('name');
        $categoryId = $request->request->get('categoryId');
        $price = $request->request->get('price');

        if (!$name || !$categoryId || !$price) {
            return $this->json(['message' => 'Missing fields'], 400);
        }

        $category = $categoryRepo->find($categoryId);

        if (!$category) {
            return $this->json(['message' => 'Invalid category'], 400);
        }

        $product = new Product();
        $product->setName($name);
        $product->setCategory($category);
        $product->setPrice((int)$price);
        $product->setIsActive(true);


        /* Upload Image */

        $imageFile = $request->files->get('image');

        if ($imageFile) {

            $fileName = uniqid() . '.webp';

            $uploadPath =
                $this->getParameter('kernel.project_dir')
                . '/public/uploads/products/';

            $manager = new ImageManager(new Driver());

            $image = $manager->read($imageFile);

            $image->scale(width: 800);

            $image->toWebp(80)
                ->save($uploadPath . $fileName);

            $product->setImage(
                '/uploads/products/' . $fileName
            );
        }

        $em->persist($product);
        $em->flush();


        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'image' => $product->getImage(),

            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName()
            ]

        ], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ProductRepository $repo,
        CategoryRepository $categoryRepo,
        EntityManagerInterface $em
    ): JsonResponse {

        $product = $repo->find($id);

        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }


        /* Read FormData */

        $name = $request->request->get('name');
        $categoryId = $request->request->get('categoryId');
        $price = $request->request->get('price');


        if ($name) {
            $product->setName($name);
        }


        /* Fix Category */

        if ($categoryId) {

            $category = $categoryRepo->find($categoryId);

            if (!$category) {
                return $this->json(['message' => 'Invalid category'], 400);
            }

            $product->setCategory($category);
        }


        if ($price) {
            $product->setPrice((int)$price);
        }


        /* Image Upload */

        $imageFile = $request->files->get('image');

        if ($imageFile) {

            $fileName = uniqid() . '.webp';

            $uploadPath =
                $this->getParameter('kernel.project_dir')
                . '/public/uploads/products/';


            $manager = new ImageManager(new Driver());

            $image = $manager->read($imageFile);

            $image->scale(width: 800);

            $image->toWebp(80)
                ->save($uploadPath . $fileName);


            $product->setImage(
                '/uploads/products/' . $fileName
            );
        }


        $em->flush();


        return $this->json([

            'id' => $product->getId(),
            'name' => $product->getName(),

            'category' => [
                'id' => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName()
            ],

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
