<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\RedisBundle\RedisBundle;
use Sebk\SmallSwoolePatterns\Array\Map;
use Sebk\SmallSwoolePatterns\Observable\Observable;
use App\RedisBundle\Dao\Resource;
use Doctrine\Persistence\ManagerRegistry;
use Sebk\SmallLogger\Contracts\LogInterface;
use Sebk\SmallLogger\Log\BasicLog;
use Sebk\SmallLoggerBundle\Service\Logger;
use Sebk\SmallOrmCore\Dao\DaoEmptyException;
use Sebk\SmallOrmCore\Dao\PersistThread;
use Sebk\SmallOrmCore\Factory\Dao;
use Sebk\SmallOrmForms\Form\FormModel;
use Swoole\Coroutine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use function Co\run;

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\User::class);

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

        // Create user if not exists
        try {
            $user = $daoFactory->get(\App\TestBundle\Dao\User::class)->findOneBy(["id" => 1]);
        } catch (DaoEmptyException $e) {
            /** @var \App\TestBundle\Model\User $user */
            $user = $daoFactory->get(\App\TestBundle\Model\User::class)->newModel();
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
            $model->setUser($user);
            $model->setName($name . " " . rand(1, 10000));
            if ($model->getValidator()->validate()) {
                $model->persistInThread($thread);
                $models[] = $model;
            }
        }
        $thread->commit();

        // Close connection
        $thread->close();

        // Return last project
        return new JsonResponse($models);
    }

    /**
     * @Route("/addProjectToUser")
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws DaoEmptyException
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Dao\DaoException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function addProjectToUser(Dao $daoFactory): JsonResponse
    {
        $user = $daoFactory->get(\App\TestBundle\Dao\User::class)->findOneBy(["id" => 1]);
        $project = $daoFactory->get(\App\TestBundle\Dao\Project::class)
            ->newModel()
            ->setName("user1Project")
        ;
        $user->setProjects([
            $project
        ]);
        $project->persist();

        return new JsonResponse($user);
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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

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
     * @Route("/testObservable")
     * @param $name
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \ReflectionException
     * @throws \Sebk\SmallOrmCore\Factory\ConfigurationException
     * @throws \Sebk\SmallOrmCore\Factory\DaoNotFoundException
     */
    public function observerMultiPersist(Dao $daoFactory)
    {
        // Get dao
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

        $findAllProjects = function ($dumpMessage) use($dao): array {
            var_dump($dumpMessage);

            for($i = 0; $i < 100; $i++) {
                $result = $dao->findBy([]);
            }

            return $result;
        };

        $observable = (new Observable($findAllProjects))
            ->subscribe(function(array $projects) {
                (new Map($projects, function ($project) {
                    $project->setName("name1" . rand(1, 10000));
                    $project->persist();
                }));
            }, function(\Exception $e) {
                echo 'Name1 Error : ' . $e->getMessage();
            });

        Coroutine\go(function() use($observable) {
            $observable
                ->run("name1")
            ;
        });

        $observable->wait();

        $observable2 = (new Observable($findAllProjects))
            ->subscribe(function (array $projects) {
                (new Map($projects, function ($project) {
                    $project->setName("name2" . rand(1, 10000));
                    $project->persist();
                }));
            }, function (\Exception $e) {
                echo 'Name2 Error : ' . $e->getMessage();
            });

        Coroutine\go(function() use($observable2) {
            $observable2
                ->run("name2");
        });

        $observable2->wait();

        return new JsonResponse();
    }

    /**
     * @Route("/testObservableUrl")
     * @param Dao $daoFactory
     * @return JsonResponse
     * @throws \App\Observable\ObservableAlreadyRanException
     */
    public function observerUrl(Dao $daoFactory)
    {
        $keys = function ($data): string {
            var_dump("keys");

            return (new Coroutine\Http\Client('www.yahoo.fr', 80, true))
                ->get('/');
        };

        $accounting = function ($data): string {
            var_dump("accounting");

            return (new Coroutine\Http\Client('www.google.com', 80, true))
                ->get('/');
        };

        $observable = (new Observable($keys))
            ->subscribe(function(string $html) {
                var_dump('finished keys');
            }, function(\Exception $e) {
                echo 'Keys Error : ' . $e->getMessage();
            })
            ->run("keys")
        ;

        $observable2 = (new Observable($accounting))
            ->subscribe(function(string $html) {
                var_dump("finished accounting");
            }, function(\Exception $e) {
                echo 'Accounting Error : ' . $e->getMessage();
            })
            ->run("accounting")
        ;

        return new JsonResponse();
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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Project::class);

        $run = [];
        $page = 1;
        $thread = new PersistThread($dao->getConnection());
        while ($result = $dao->findPaginated($page, 10)) {
            // Persist
            $run[] = (new Map($result, function($project) use($thread) {
                $project->setName("test " . rand(1, 10000));
                $project->persist();
            }))->run();
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
        $dao = $daoFactory->get(\App\TestBundle\Dao\User::class);
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
        $dao = $daoFactory->get(Resource::class);

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
        $dao = $daoFactory->get(Resource::class);

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
        $dao = $daoFactory->get(Resource::class);

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Test::class);

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
        $dao = $daoFactory->get(\App\TestBundle\Dao\Test::class);

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

        $dao = $daoFactory->get(\App\TestBundle\Dao\Test::class);

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