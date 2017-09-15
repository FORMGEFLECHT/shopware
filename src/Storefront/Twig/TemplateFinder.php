<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Storefront\Twig;

use AppKernel;
use Shopware\Storefront\Theme\Theme;
use Symfony\Bundle\TwigBundle\Loader\FilesystemLoader;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class TemplateFinder
{
    /**
     * @var array
     */
    private $directories = [];

    /**
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * @var array[]
     */
    private $queue = [];

    /**
     * @param AppKernel        $kernel
     * @param FilesystemLoader $loader
     */
    public function __construct(AppKernel $kernel, FilesystemLoader $loader)
    {
        $this->loader = $loader;

        array_map([$this, 'addBundle'], $kernel::getPlugins()->all());
        array_map([$this, 'addTheme'], $kernel->getThemes());

        $this->loader->addPath($kernel->getRootDir() . '/../src/Api/Resources/views', 'Api');
    }

    public function addBundle(BundleInterface $bundle): void
    {
        $directory = $bundle->getPath() . '/Resources/views/';
        if (!file_exists($directory)) {
            return;
        }

        $this->loader->addPath($directory, $bundle->getName());
        $this->directories[] = '@' . $bundle->getName();
    }

    public function addTheme(Theme $theme): void
    {
        $directory = $theme->getPath() . '/Resources/views/';
        if (!file_exists($directory)) {
            return;
        }

        $this->loader->addPath($directory, $theme->getName());
        $this->directories[] = '@' . $theme->getName();
    }

    /**
     * @throws \Twig_Error_Loader
     */
    public function find(string $template, $wholeInheritance = false): string
    {
        if (!$wholeInheritance && array_key_exists($template, $this->queue)) {
            $queue = $this->queue[$template];
        } else {
            $queue = $this->queue[$template] = $this->directories;
        }

        foreach ($queue as $index => $prefix) {
            $name = $prefix . '/' . $template;

            unset($this->queue[$template][$index]);

            if ($this->loader->exists($name)) {
                return $name;
            }
        }

        throw new \Twig_Error_Loader(sprintf('Unable to load template "%s".', $template));
    }
}