<?php

namespace Adshares\Adclassify\Security\Factory;

use Adshares\Adclassify\Security\Authentication\Provider\WsseProvider;
use Adshares\Adclassify\Security\Firewall\WsseListener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WsseFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint): array
    {
        $providerId = 'security.authentication.provider.wsse.' . $id;
        $container->setDefinition($providerId, new ChildDefinition(WsseProvider::class));

        $listenerId = 'security.authentication.listener.wsse.' . $id;
        $container->setDefinition($listenerId, new ChildDefinition(WsseListener::class));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'wsse';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
