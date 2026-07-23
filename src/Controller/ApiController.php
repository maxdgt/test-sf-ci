<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/version', name: 'version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        return new JsonResponse([
            'name' => 'test-sf-ci',
            'symfony' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'php' => \PHP_VERSION,
        ]);
    }

    #[Route('/echo/{message}', name: 'echo', methods: ['GET'])]
    public function echoMessage(string $message): JsonResponse
    {
        return new JsonResponse([
            'echo' => $message,
        ]);
    }
}
