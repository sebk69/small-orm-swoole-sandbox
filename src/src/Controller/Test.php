<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Test
{
    /**
     * @Route("/")
     * @return JsonResponse
     */
    public function test()
    {
        return new JsonResponse(["ok"]);
    }
}