<?php

namespace App;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\AbstractKernel;
use Symfony\Component\DependencyInjection\Kernel\KernelTrait;

class Kernel extends AbstractKernel
{
    use KernelTrait;

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }

    protected function build(ContainerBuilder $container): void
    {
        // TwigBundle decorates "error_renderer.html" to render pretty HTML error
        // pages for Twig exceptions. That service only exists when framework-bundle's
        // HttpKernel error-rendering chain is loaded, which this console app doesn't use.
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach (['twig.error_renderer.html', 'error_renderer.html'] as $id) {
                    if ($container->hasDefinition($id)) {
                        $container->removeDefinition($id);
                    }
                }
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
