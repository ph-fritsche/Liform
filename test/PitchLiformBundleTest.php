<?php

/*
 * This file is part of the Pitch\Liform package.
 *
 * (c) Philipp Fritsche <ph.fritsche@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pitch\Liform;

use Pitch\AdrBundle\DependencyInjection\PitchAdrExtension;
use Pitch\AdrBundle\PitchAdrBundle;
use Pitch\Liform\DependencyInjection\Compiler\ExtensionCompilerPass;
use Pitch\Liform\DependencyInjection\Compiler\TransformerCompilerPass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PitchLiformBundleTest extends KernelTestCase
{
    protected static function getKernelClass()
    {
        return get_class(new class('test', true) extends Kernel
        {
            public function getProjectDir()
            {
                return $this->dir ??= sys_get_temp_dir() . '/PitchForm-' . uniqid() . '/';
            }

            public function registerBundles(): iterable
            {
                return [
                    new FrameworkBundle(),
                    new PitchAdrBundle(),
                    new PitchLiformBundle(),
                ];
            }

            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                $loader->load(function (ContainerBuilder $containerBuilder) {
                    $containerBuilder->setParameter('kernel.secret', 'secret');
                });

                $loader->load(function (ContainerBuilder $containerBuilder) {
                    $containerBuilder->prependExtensionConfig(
                        PitchAdrExtension::ALIAS,
                        ['defaultResponseHandlers' => false],
                    );
                });

                $loader->load(function (ContainerBuilder $containerBuilder) {
                    $containerBuilder->setDefinition(
                        TranslatorInterface::class,
                        new Definition(TranslatorMock::class),
                    );

                    $containerBuilder
                        ->setAlias('test.liform', LiformInterface::class)
                            ->setPublic(true)
                    ;
                });
            }
        });
    }

    public function testBuild()
    {
        $container = new ContainerBuilder();

        $bundle = new PitchLiformBundle();
        $bundle->build($container);

        $compilerPasses = \array_map(fn($v) => get_class($v), $container->getCompilerPassConfig()->getPasses());

        $this->assertContains(ExtensionCompilerPass::class, $compilerPasses);
        $this->assertContains(TransformerCompilerPass::class, $compilerPasses);
    }

    public function testLiformService()
    {
        static::bootKernel();

        /** @var FormFactoryInterface */
        $formFactory = static::$container->get('form.factory');
        $form = $formFactory->create(SymfonyFormType::class);

        $this->compareJson(
            __DIR__ . '/../docs/build/SymfonyFormType.config.json',
            SymfonyFormType::$lastConfig,
        );

        /** @var LiformInterface */
        $liform = static::$container->get('test.liform');

        $this->compareJson(
            __DIR__ . '/../docs/build/SymfonyFormType.json',
            $liform->transform($form->createView()),
        );
    }

    private function compareJson(
        $file,
        $object
    ) {
        $jsonFlags = \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR;
        $fileContent = \file_get_contents($file);
        $fileObject = \json_decode($fileContent, true, 512, $jsonFlags);

        $objectEncoded = \json_encode($object, $jsonFlags, 512);
        $objectNormalized = \json_decode($objectEncoded, true, 512, $jsonFlags);

        $this->assertEquals($fileObject, $objectNormalized);
    }

    public function testLiformResponder()
    {
        static::bootKernel();

        /** @var FormFactoryInterface */
        $formFactory = static::$container->get('form.factory');
        $form = $formFactory->createNamedBuilder('fooForm', SymfonyFormType::class)->getForm();
        $form->setData(['TextType' => 'foo']);

        $event = new ViewEvent(
            static::$kernel,
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $form,
        );

        /** @var EventDispatcherInterface */
        $dispatcher = static::$container->get('event_dispatcher');
        $dispatcher->dispatch($event, KernelEvents::VIEW);

        $this->assertFalse($event->hasResponse());
        $result = $event->getControllerResult();

        $this->assertArrayHasKey('name', $result);
        $this->assertSame('fooForm', $result['name']);

        $this->assertArrayHasKey('value', $result);
        $this->assertObjectHasAttribute('TextType', $result['value']);
        $this->assertSame('foo', $result['value']->TextType);
    }
}
