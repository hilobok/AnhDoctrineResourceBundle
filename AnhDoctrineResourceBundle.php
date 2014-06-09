<?php

namespace Anh\DoctrineResourceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Anh\DoctrineResourceBundle\DependencyInjection\Compiler\RepositoryFactoryCompilerPass;
use Anh\DoctrineResourceBundle\DependencyInjection\Compiler\ResourceServicesCompilerPass;

class AnhDoctrineResourceBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RepositoryFactoryCompilerPass());
        $container->addCompilerPass(new ResourceServicesCompilerPass(), PassConfig::TYPE_OPTIMIZE);
    }
}