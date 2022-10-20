<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\RedisBundle\Dao\Resource;
use Doctrine\Persistence\ManagerRegistry;
use Sebk\SmallLogger\Contracts\LogInterface;
use Sebk\SmallLogger\Log\BasicLog;
use Sebk\SmallLoggerBundle\Service\Logger;
use Sebk\SmallOrmCore\Dao\DaoEmptyException;
use Sebk\SmallOrmCore\Dao\PersistThread;
use Sebk\SmallOrmCore\Factory\Dao;
use Sebk\SmallOrmForms\Form\FormModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class Test extends AbstractController
{

    /**
     * @Route("/userCreate")
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function createUser(Dao $daoFactory, Logger $logger): JsonResponse
    {
        // Get dao
        $dao = $daoFactory->get("TestBundle", "User");

        // Create model
        /** @var \App\TestBundle\Model\User $model */
        $model = $dao->newModel();
        $model->setName("Test user " . rand(1, 10000));

        // persist
        $model->persist();

        $logger->log(new BasicLog(new \DateTime(), LogInterface::ERR_LEVEL_INFO, 'User ' . $model->getName() . ' created'));
        return new JsonResponse("That's done !");
    }

    /**
     * @Route("/multiPersist")
     * @param Dao $daoFactory
     * @return Response
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function multiPersist(Dao $daoFactory, Logger $logger)
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
        $logger->info('Ceci est un message');

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

        // Create user if not exists
        try {
            $daoFactory->get("TestBundle", "User")->findOneBy(["id" => 1]);
        } catch (DaoEmptyException $e) {
            /** @var \App\TestBundle\Model\User $user */
            $user = $daoFactory->get("TestBundle", "User")->newModel();
            $user->setName("John Do");
            $user->persist();
        }

        // Create thread
        $thread = new PersistThread($dao->getConnection());

        // Create 100 projects for user
        $thread->startTransaction();
        $thread->setFlushOnInsert();
        $models = [];
        for($i = 0; $i < 100; $i++) {
            /** @var \App\TestBundle\Model\Project $model */
            $model = $dao->newModel();
            $model->setUserId(1);
            $model->setName($name . " " . rand(1, 10000));
            $thread->pushPersist($model);
            $models[] = $model;
        }
        $thread->commit();

        foreach ($models as $model) {
            $model->setName($model->getName() . " persisted");
            $model->persist();
        }

        // Close connection
        $thread->close();

        // Return last project
        return new JsonResponse($models);
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
     * @Route("/redisPersist")
     * @param Dao $daoFactory
     * @return mixed
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function redisPersist(Dao $daoFactory)
    {
        /** @var Resource $dao */
        $dao = $daoFactory->get("RedisBundle", "Project");

        for ($i = 0; $i < 100; $i++) {
            $model = $dao->newModel();
            $model->setId($i);
            $model->setName("Resource" . $i);
            $model->persist();
        }

        return new JsonResponse($model);
    }

    /**
     * @Route("/redisGet")
     * @param Dao $daoFactory
     * @return mixed
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function redisGet(Dao $daoFactory)
    {
        /** @var Resource $dao */
        $dao = $daoFactory->get("RedisBundle", "Resource");

        $models = $dao->findBy(range(0, 99));

        return new JsonResponse($models);
    }

    /**
     * @Route("/redisDel")
     * @param Dao $daoFactory
     * @return mixed
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function redisDel(Dao $daoFactory)
    {
        /** @var Resource $dao */
        $dao = $daoFactory->get("RedisBundle", "Resource");

        for($i = 0; $i < 100; $i++) {
            $dao->getResult($dao->createDeleteBuilder()->del($i));
        }

        return new JsonResponse();
    }

    /**
     * @Route("persistTests")
     * @param Dao $daoFactory
     * @return void
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function testTypePersist(Dao $daoFactory)
    {
        /** @var \App\TestBundle\Dao\Test $dao */
        $dao = $daoFactory->get("TestBundle", "Test");

        /** @var \App\TestBundle\Model\Test $test */
        $test = $dao->newModel();

        $test->setBigint(1);
        $test->setChar("test");
        $test->setDate(new \DateTime());
        $test->setDatetime(new \DateTime());
        $test->setDecimal(1.21);
        $test->setDouble(1);
        $test->setFloat(1.21);
        $test->setJson(["test" => 1, "test2" => ""]);
        $test->setLongtext("test");
        $test->setMediumint(1);
        $test->setMediumtext(1);
        $test->setNchar("test");
        $test->setNvarchar("test");
        $test->setReal(1);
        $test->setTestcol(1);
        $test->setTestcol1("test");
        $test->setVarchar("test");
        $test->setSmallint(1);
        $test->setTinyint(1);
        $test->setTinytext("test");
        $test->setBoolean(true);

        $test->persist();

        return new Response("Done !");
    }

    /**
     * @Route("getTest/{id}")
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function getTest($id, Dao $daoFactory)
    {
        /** @var \App\TestBundle\Dao\Test $dao */
        $dao = $daoFactory->get("TestBundle", "Test");

        return new JsonResponse($dao->findBy(["int" => $id]));
    }

    /**
     * Test form class with all types
     * @Route("testForm/{id}", methods={"POST"})
     * @param Dao $daoFactory
     * @param Request $request
     * @return JsonResponse
     * @throws DaoEmptyException
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     * @throws \Sebk\SmallOrmForms\Form\FieldException
     * @throws \Sebk\SmallOrmForms\Form\FieldNotFoundException
     * @throws \Sebk\SmallOrmForms\Type\TypeNotFoundException
     */
    public function testForm($id, Dao $daoFactory, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $dao = $daoFactory->get("TestBundle", "Test");

        $model = $dao->findOneBy(["int" => $id]);

        $form = (new FormModel())
            ->fillFromModel($model)
            ->fillFromArray($data)
        ;

        $model = $form->fillModel();

        $model->persist();

        return new JsonResponse($model);
    }

}