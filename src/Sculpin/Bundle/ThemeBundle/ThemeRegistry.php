<?php

namespace Sculpin\Bundle\ThemeBundle;

use Dflydev\Symfony\FinderFactory\FinderFactoryInterface;
use Symfony\Component\Yaml\Yaml;

class ThemeRegistry
{
    private $finderFactory;
    private $directory;
    private $activeTheme;

    public function __construct(FinderFactoryInterface $finderFactory, $directory, $activeTheme = null)
    {
        $this->finderFactory = $finderFactory;
        $this->directory = $directory;
        $this->activeTheme = $activeTheme;
    }

    public function listThemes()
    {
        if (! file_exists($this->directory)) {
            return array();
        }

        $directories = $this
            ->finderFactory->createFinder()
            ->directories()
            ->ignoreVCS(true)
            ->depth('== 0')
            ->in($this->directory);

        $themes = array();

        foreach ($directories as $directory) {
            $theme = array('name' => basename($directory), 'path' => $directory);
            if (file_exists($directory.'/theme.yml')) {
                $theme = array_merge(Yaml::parse(file_get_contents($directory.'/theme.yml')), $theme);
            }

            if (file_exists($directory.'/_views') and is_dir($directory.'/_views')) {
                $theme['_views'] = $directory.'/_views';
            }

            $themes[$theme['name']] = $theme;
        }

        return $themes;
    }

    public function findActiveTheme()
    {
        $themes = $this->listThemes();

        if (! isset($themes[$this->activeTheme])) {
            return null;
        }

        $theme = $themes[$this->activeTheme];
        if (isset($theme['parent'])) {
            if (! isset($themes[$theme['parent']])) {
                throw new \RuntimeException(sprintf("Theme %s is a child of nonexistent parent theme %s", $this->activeTheme, $theme['parent']));
            }

            $theme['parent'] = $themes[$theme['parent']];
        }

        return $theme;
    }
}
