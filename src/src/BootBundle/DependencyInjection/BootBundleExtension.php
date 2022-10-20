<?php
/**
 * This file is a part of small-logger-bundle
 * Copyright 2020 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallLoggerBundle\DependencyInjection;


use Sebk\SmallLogger\Contracts\LogInterface;
use Sebk\SmallLogger\Log\BasicLog;
use Sebk\SmallLoggerBundle\Service\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class BootBundleExtension extends Extension
{

    /**
     * Load bundle
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        /** @var Logger $logger */
        $logger = $container->get('small_logger');

        $logger->registerShortcut('info', function(Logger $logger, string $message) {
            $logger->log(new BasicLog(new \DateTime(), LogInterface::ERR_LEVEL_INFO, $message));
        });
    }

}