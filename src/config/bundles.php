<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    K911\Swoole\Bridge\Symfony\Bundle\SwooleBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Sebk\SmallOrmBundle\SebkSmallOrmBundle::class => ['all' => true],
    App\TestBundle\TestBundle::class => ['all' => true],
    App\RedisBundle\RedisBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Sebk\SmallLoggerBundle\SebkSmallLoggerBundle::class => ['all' => true],
    App\BootBundle\BootBundle::class => ['all' => true],
];
