<?php

namespace CoreShop\Bundle\NotificationBundle\DependencyInjection\Compiler;

use CoreShop\Bundle\ResourceBundle\Form\Registry\FormTypeRegistry;
use CoreShop\Bundle\RuleBundle\DependencyInjection\Compiler\RegisterActionConditionPass;
use CoreShop\Component\Registry\ServiceRegistry;
use CoreShop\Component\Rule\Condition\ConditionCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractNotificationRulePass extends RegisterActionConditionPass
{
    /**
     * @return string
     */
    protected abstract function getType();

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->getRegistryIdentifier()) || !$container->has($this->getFormRegistryIdentifier())) {
            return;
        }

        $registries = [];
        $formRegistries = [];
        $types = [];
        $registeredTypes = [];

        $registry = $container->getDefinition($this->getRegistryIdentifier());
        $formRegistry = $container->getDefinition($this->getFormRegistryIdentifier());

        $map = [];
        foreach ($container->findTaggedServiceIds($this->getTagIdentifier()) as $id => $attributes) {
            foreach ($attributes as $tag) {
                if (!isset($tag['type'], $tag['form-type'], $tag['notification-type'])) {
                    throw new \InvalidArgumentException('Tagged Condition `' . $id . '` needs to have `type`, `form-type` and `notification-type`` attributes.');
                }

                $type = $tag['notification-type'];

                if (!array_key_exists($type, $registries)) {
                    $registries[$type] = new Definition(
                        ServiceRegistry::class,
                        [ConditionCheckerInterface::class, 'notification-rule-' . $this->getType() . '-' . $type]
                    );

                    $formRegistries[$type] = new Definition(
                        FormTypeRegistry::class
                    );

                    $types[] = $type;

                    $container->setDefinition($this->getRegistryIdentifier() . '.' . $type, $registries[$type]);
                    $container->setDefinition($this->getFormRegistryIdentifier() . '.' . $type, $formRegistries[$type]);
                }

                $map[$tag['notification-type']][$tag['type']] = $tag['type'];

                $registries[$type]->addMethodCall('register', [$tag['type'], new Reference($id)]);
                $formRegistries[$type]->addMethodCall('add', [$tag['type'], 'default', $tag['form-type']]);

                if (!in_array($tag['type'], $registeredTypes)) {
                    $registry->addMethodCall('register', [$tag['type'], new Reference($id)]);
                    $formRegistry->addMethodCall('add', [$tag['type'], 'default', $tag['form-type']]);

                    $registeredTypes[$tag['type']] = $tag['type'];
                }
            }
        }

        foreach ($map as $type => $realMap) {
            $container->setParameter($this->getIdentifier() . '.' . $type, $realMap);
        }

        $container->setParameter($this->getIdentifier() . '.types', $types);
        $container->setParameter($this->getIdentifier(), $registeredTypes);
    }
}