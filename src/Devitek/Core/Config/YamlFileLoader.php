<?php namespace Devitek\Core\Config;

use Illuminate\Config\FileLoader;
use Symfony\Component\Yaml\Parser;

class YamlFileLoader extends FileLoader
{
    protected function getAllowedFileExtensions()
    {
        return ['php', 'yml', 'yaml'];
    }

    public function load($environment, $group, $namespace = null)
    {
        $items = [];
        $path  = $this->getPath($namespace);

        if (is_null($path)) {
            return $items;
        }

        foreach ($this->getAllowedFileExtensions() as $extension) {
            $file = "{$path}/{$group}." . $extension;

            if ($this->files->exists($file)) {
                $items = $this->mergeEnvironmentWithYamlSupport($items, $file, $extension);
            }

            $file = "{$path}/{$environment}/{$group}." . $extension;

            if ($this->files->exists($file)) {
                $items = $this->mergeEnvironmentWithYamlSupport($items, $file, $extension);
            }
        }

        return $items;
    }

    protected function mergeEnvironmentWithYamlSupport(array $items, $file, $extension)
    {
        return array_replace_recursive($items, $this->parseContent($extension, $file));
    }

    public function exists($group, $namespace = null)
    {
        $key = $group . $namespace;

        if (isset($this->exists[$key])) {
            return $this->exists[$key];
        }

        $path = $this->getPath($namespace);

        if (is_null($path)) {
            return $this->exists[$key] = false;
        }

        foreach ($this->getAllowedFileExtensions() as $extension) {
            $file = "{$path}/{$group}." . $extension;

            if ($exists = $this->files->exists($file)) {
                return $this->exists[$key] = $exists;
            }
        }

        return $this->exists[$key] = false;
    }

    public function cascadePackage($env, $package, $group, $items)
    {
        foreach ($this->getAllowedFileExtensions() as $extension) {
            $file = "packages/{$package}/{$group}." . $extension;

            if ($this->files->exists($path = $this->defaultPath . '/' . $file)) {
                $items = array_merge($items, $this->parseContent($extension, $path));
            }

            $path = $this->getPackagePath($env, $package, $group);

            if ($this->files->exists($path)) {
                $items = array_merge($items, $this->parseContent($extension, $path));
            }
        }

        return $items;
    }

    protected function getPackagePath($env, $package, $group)
    {
        $result = [];

        foreach ($this->getAllowedFileExtensions() as $extension) {
            $file     = "packages/{$package}/{$env}/{$group}." . $extension;
            $result[] = $this->defaultPath . '/' . $file;
        }

        return $result;
    }

    protected function parseContent($format, $file)
    {
        $content = null;

        switch ($format) {
            case 'php':
                $content = $this->files->getRequire($file);
                break;
            case 'yml':
            case 'yaml':
                $parser  = new Parser();
                $content = $parser->parse(file_get_contents($file));
                break;
        }

        return $content;
    }
} 