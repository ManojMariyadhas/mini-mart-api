<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{

    #[Route('', methods: ['GET'])]
    public function list(CategoryRepository $repo): JsonResponse
    {

        $categories = $repo->findBy(
            ['isActive' => true],
            ['name' => 'ASC']
        );

        $data = [];

        foreach ($categories as $c) {

            $data[] = [

                'id' => $c->getId(),
                'name' => $c->getName()

            ];
        }

        return $this->json($data);
    }
}
