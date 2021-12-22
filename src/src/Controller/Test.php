<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Sebk\SmallOrmCore\Dao\DaoEmptyException;
use Sebk\SmallOrmCore\Dao\PersistThread;
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
     * Test average : 168ms
     * @Route("/multiPersist")
     * @param Dao $daoFactory
     * @return Response
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function multiPersist(Dao $daoFactory)
    {
        // Get dao
        $dao = $daoFactory->get("TestBundle", "Project");

        // Get projects
        $result = $dao->findBy([]);

        // Rename them in thread
        $thread = new PersistThread($dao->getConnection());

        // Rename them in good practice
        $thread->startTransaction();
        foreach ($result as $model) {
            $model->setName("renamed : " . rand(1, 10000));
            $thread->pushPersist($model);
        }
        $thread->commit();

        // Always close thread to release connection
        $thread->close();

        return new Response("That's done !");
    }

    /**
     * Test average : average 16ms
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
        // Get dao
        $dao = $daoFactory->get("TestBundle", "Project");

        // Create thread
        $thread = new PersistThread($dao->getConnection());

        // Create user if not exists
        try {
            $daoFactory->get("TestBundle", "User")->findOneBy(["id" => 1]);
        } catch (DaoEmptyException $e) {
            /** @var \App\TestBundle\Model\User $user */
            $user = $daoFactory->get("TestBundle", "User")->newModel();
            $user->setName("John Do");
            $user->persist();
        }

        // Create 100 projects for user
        $thread->startTransaction();
        for($i = 0; $i < 100; $i++) {
            /** @var \App\TestBundle\Model\Project $model */
            $model = $dao->newModel();
            $model->setUserId(1);
            $model->setName($name . " " . rand(1, 10000));
            $thread->pushPersist($model);
        }
        $thread->commit();

        // Close connection
        $thread->close();

        // Return last project
        return new JsonResponse($model);
    }

    /**
     * Test on 1000 : 1320ms
     * @Route("/deleteProjects")
     * @param Dao $daoFactory
     * @return void
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function deleteProjects(Dao $daoFactory)
    {
        // Get dao
        $dao = $daoFactory->get("TestBundle", "Project");

        // Get all projects
        $projects = $dao->findBy([]);

        // Create thread
        $thread = new PersistThread($dao->getConnection());

        // Delete all
        foreach($projects as $project) {
            /** @var \App\TestBundle\Model\Project $model */
            $thread->pushDelete($project);
        }

        // Flush and close
        $thread->flush();
        $thread->close();

        return new Response("That's done !");
    }

    /**
     * Test average : 1201ms
     * Rename projects and return modified models
     * @Route("/unitMultiPersist/{name}")
     * @param $name
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function unitMultiPersist($name, Dao $daoFactory)
    {
        // Get dao
        $dao = $daoFactory->get("TestBundle", "Project");

        // Get all projects
        $projects = $dao->findBy([]);

        $projects = \Swoole\Coroutine\map($projects, function($project) use($dao, $name) {
            $project->setName($name . rand(1, 10000));
            $project->persist();

            return $project;
        });

        return new JsonResponse($projects);
    }

    /**
     * Test average : 1418ms
     * @Route("/persistWithPagination")
     * @param Dao $daoFactory
     * @return Response
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function persistWithPagination(Dao $daoFactory)
    {
        /** @var \App\TestBundle\Dao\Project $dao */
        $dao = $daoFactory->get("TestBundle", "Project");

        $page = 1;
        $thread = new PersistThread($dao->getConnection());
        while ($result = $dao->findPaginated($page, 10)) {
            // Persist
            \Swoole\Coroutine\map($result, function($project) use($thread) {
                $project->setName("test " . rand(1, 10000));
                $project->persist();
            });
            $page++;
        }
        $thread->close();

        return new Response("That's done !");
    }

    /**
     * Test average : 169ms
     * @Route("/massFindOne")
     * @param Dao $daoFactory
     * @return Response
     * @throws DaoEmptyException
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function massFindOne(Dao $daoFactory)
    {
        $dao = $daoFactory->get("TestBundle", "User");
        \Swoole\Coroutine\map(range(1, 1000), function ($i) use ($dao) {
            $dao->findOneBy(["id" => 1]);
        });

        return new Response("That's done !");
    }

    /**
     * Test average : 261ms
     * @Route("/doctrine/multiPersist")
     * @param Dao $daoFactory
     * @return Response
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function multiPersistDoctrine(ManagerRegistry $managerRegistry)
    {
        // Get repo
        $repo = $managerRegistry->getRepository(Project::class);

        // Get projects
        $result = $repo->findAll();

        // Rename them
        $managerRegistry->getConnection()->beginTransaction();
        foreach ($result as $entity) {
            $entity->name = "oups";
            $managerRegistry->getManager()->persist($entity);
        }

        // Flush
        $managerRegistry->getManager()->flush();

        // Delete them (without flushing them)
        foreach ($result as $entity) {
            $managerRegistry->getManager()->remove($entity);
        }

        // Rollback (flushed operations or not)
        $managerRegistry->getConnection()->rollBack();

        // Rename them in good practice
        $managerRegistry->getConnection()->beginTransaction();
        foreach ($result as $entity) {
            $entity->name = "renamed : " . rand(1, 10000);
            $managerRegistry->getManager()->persist($entity);
        }
        $managerRegistry->getManager()->flush();
        $managerRegistry->getConnection()->commit();

        return new Response("That's done !");
    }


    /**
     * Test average : 5ms
     * @Route("/doctrine/createProject/{name}")
     * @param $name
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function createProjectDoctrine($name, ManagerRegistry $managerRegistry)
    {
        // Get repo
        $repo = $managerRegistry->getRepository(Project::class);

        // Create user if not exists
        try {
            $user =  $managerRegistry->getRepository(User::class)->findOneBy(["id" => 1]);
        } catch (DaoEmptyException $e) {
            /** @var \App\TestBundle\Model\User $user */
            $user = new User();
            $user->setName("John Do");
            $managerRegistry->getManager()->persist($user);
        }

        // Create 100 projects for user
        $managerRegistry->getConnection()->beginTransaction();
        for($i = 0; $i < 100; $i++) {
            $model = new Project();
            $model->user = $user;
            $model->name = $name . " " . rand(1, 10000);
            $managerRegistry->getManager()->persist($model);
        }
        $managerRegistry->getConnection()->commit();

        // Return last project
        return new JsonResponse($model);
    }

    /**
     * Test on 1000 : 3ms
     * @Route("/doctrine/deleteProjects")
     * @param Dao $daoFactory
     * @return void
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function deleteProjectsDoctrine(ManagerRegistry $managerRegistry)
    {
        // Get repo
        $repo = $managerRegistry->getRepository(Project::class);

        // Get all projects
        $projects = $repo->findBy([]);

        // Delete all
        foreach($projects as $project) {
            /** @var \App\TestBundle\Model\Project $model */
            $managerRegistry->getManager()->remove($project);
        }

        // Flush and close
        $managerRegistry->getManager()->flush();

        return new Response("That's done !");
    }

    /**
     * Test average : 4030ms
     * Rename projects and return modified models
     * @Route("/doctrine/unitMultiPersist/{name}")
     * @param $name
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function unitMultiPersistDoctrine($name, ManagerRegistry $managerRegistry)
    {
        // Get dao
        $repo = $managerRegistry->getRepository(Project::class);

        // Get all projects
        $projects = $repo->findBy([]);

        foreach ($projects as $project) {
            $project->name = $name . rand(1, 10000);
            $managerRegistry->getManager()->persist($project);
            $managerRegistry->getManager()->flush();
        }

        return new JsonResponse($projects);
    }

    /**
     * Test average : 2506ms
     * @Route("/doctrine/persistWithPagination")
     * @param Dao $daoFactory
     * @return Response
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function persistWithPaginationDoctrine(ManagerRegistry $managerRegistry)
    {
        /** @var \App\TestBundle\Dao\Project $dao */
        $repo = $managerRegistry->getRepository(Project::class);

        $page = 1;
        while ($result = $repo->listPaginated($page, 10)) {
            foreach ($result as $project) {
                $project->name = "test " . rand(1, 10000);
                $managerRegistry->getManager()->persist($project);
                $managerRegistry->getManager()->flush();
            }
            $page++;
        }

        return new Response("That's done !");
    }

    /**
     * Test average : 163ms
     * @Route("/doctrine/massFindOne")
     * @param ManagerRegistry $managerRegistry
     * @return Response
     */
    public function massFindOneDoctrine(ManagerRegistry $managerRegistry)
    {
        /** @var \App\TestBundle\Dao\Project $dao */
        $repo = $managerRegistry->getRepository(User::class);

        for($i = 0; $i < 1000; $i++) {
            $repo->findOneById(1);
        }

        return new Response("That's done !");
    }


}