<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Sebk\SmallOrmCore\Factory\Dao;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class Test extends AbstractController
{
    /**
     * @Route("/")
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function test(Dao $daoFactory)
    {
        $dao = $daoFactory->get("TestBundle", "Project");
        $result = \Swoole\Coroutine\map ([0], function ($value) use ($dao) {
            return $dao->findBy([], [["user"]]);
        });
        foreach ($result[0] as $project)
            $project->persist();

        return new JsonResponse($result);
    }

    /**
     * @Route("/createProject/{name}")
     * @param $name
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function createProject($name, Dao $daoFactory)
    {
        $dao = $daoFactory->get("TestBundle", "Project");
        /** @var \App\TestBundle\Model\Project $model */
        $model = $dao->newModel();
        $model->setUserId(1);
        $model->setName($name);
        $model->persist();

        return new JsonResponse($model);
    }


    /**
     * @Route("/doctrine")
     * @param ManagerRegistry $managerRegistry
     * @return JsonResponse
     */
    public function testDoctrine(ManagerRegistry $managerRegistry, SerializerInterface $serializer)
    {
        $repo = $this->getDoctrine()->getRepository(Project::class);

        $result = $repo->findAll();
        foreach ($result as $project)
            $managerRegistry->getManager()->persist($project);

        return new Response($serializer->serialize($result, JsonEncoder::FORMAT));
    }
}