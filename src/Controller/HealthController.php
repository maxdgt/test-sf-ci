<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    #[Route('/ping', name: 'app_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'pong',
        ]);
    }
}
