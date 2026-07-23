<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Hello, World!',
        ]);
    }

    #[Route('/hello/{name}', name: 'app_hello', methods: ['GET'])]
    public function hello(string $name): JsonResponse
    {
        return new JsonResponse([
            'message' => sprintf('Hello, %s!', $name),
        ]);
    }
}
