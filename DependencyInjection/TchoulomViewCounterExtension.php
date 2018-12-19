<?php

/**
 * This file is part of the TchoulomViewCounterBundle package.
 *
 * @package    TchoulomViewCounterBundle
 * @author     Original Author <tchoulomernest@yahoo.fr>
 *
 * (c) Ernest TCHOULOM <https://www.tchoulom.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tchoulom\ViewCounterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Tchoulom\ViewCounterBundle\Exception\RuntimeException;
use Tchoulom\ViewCounterBundle\TchoulomViewCounterBundle;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class TchoulomViewCounterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $viewIntervals = $this->beforeProcess($configs);
        $container->setParameter('view_interval', $viewIntervals);
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Before processing the configuration
     * Gets the first choice.
     *
     * @param $configs
     *
     * @return array
     */
    public function beforeProcess($configs)
    {
        $uniqueElt = [];

        $viewInterval = $configs[0]['view_interval'];

        if (empty($viewInterval)) {
            throw new RuntimeException(vsprintf('You must choose one of the following values: %s, %s, %s, %s.', TchoulomViewCounterBundle::SUPPORTED_INTERVAL));
        }

        foreach ($viewInterval as $key => $config) {
            if (!in_array($key, TchoulomViewCounterBundle::SUPPORTED_INTERVAL)) {
                throw new RuntimeException(sprintf('The key "%s" is not supported.', $key) . vsprintf('You must choose one of the following values: %s, %s, %s, %s.', TchoulomViewCounterBundle::SUPPORTED_INTERVAL));
            }

            if (!is_int($config)) {
                throw new RuntimeException(sprintf('The value "%s" must be an integer.', $config));
            }

            $uniqueElt[$key] = $config;
            break;
        }

        return [$uniqueElt];
    }
}
