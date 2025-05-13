<?php

namespace Plugins\DcatSaas\Console\Commands\Traits;

use Plugins\DcatSass\Support\Config\GenerateConfigReader;
use Plugins\DcatSaas\Support\Json;
use Plugins\DcatSaas\Support\Stub;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait StubTrait
{
    protected $runningAsRootDir = false;
    protected $buildClassName = null;

    protected function buildClass($name)
    {
        $this->runningAsRootDir = false;
        if (str_starts_with($name, 'App')) {
            $this->runningAsRootDir = true;
            $this->buildClassName = $name;
        }

        $content = $this->getStubContents($this->getStub());

        return $content;
    }

    protected function getPath($name)
    {
        $path = parent::getPath($name);

        $this->type = $path;

        return $path;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    protected function getStubName(): ?string
    {
        return null;
    }

    /**
     * implement from \Illuminate\Console\GeneratorCommand.
     *
     * @return string
     *
     * @see \Illuminate\Console\GeneratorCommand
     */
    protected function getStub(): string
    {
        $stubName = $this->getStubName();
        if (! $stubName) {
            throw new \RuntimeException('Please provider stub name in getStubName method');
        }

        $baseStubPath = base_path("stubs/{$stubName}.stub");
        if (file_exists($baseStubPath)) {
            return $baseStubPath;
        }

        $stubPath = dirname(__DIR__)."/stubs/{$stubName}.stub";
        if (file_exists($stubPath)) {
            return $stubPath;
        }

        throw new \RuntimeException("stub path does not exists: {$stubPath}");
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        return class_basename($this->argument('name'));
    }

    /**
     * Get the contents of the specified stub file by given stub name.
     *
     * @param $stub
     * @return string
     */
    protected function getStubContents($stubPath)
    {
        $method = sprintf('get%sStubPath', Str::studly(strtolower($stubPath)));

        // custom stubPath
        if (method_exists($this, $method)) {
            $stubFilePath = $this->$method();
        } else {
            // run in command: fresns new Xxx
            $stubFilePath = dirname(__DIR__)."/stubs/{$stubPath}.stub";

            if (file_exists($stubFilePath)) {
                $stubFilePath = $stubFilePath;
            }
            // run in command: fresns make:xxx
            else {
                $stubFilePath = $stubPath;
            }
        }

        $mimeType = File::mimeType($stubFilePath);
        if (
            str_contains($mimeType, 'application/')
            || str_contains($mimeType, 'text/')
        ) {
            $stubFile = new Stub($stubFilePath, $this->getReplacement($stubFilePath));
            $content = $stubFile->render();
        } else {
            $content = File::get($stubFilePath);
        }

        // format json style
        if (str_contains($stubPath, 'json')) {
            $content = Json::make()->decode($content)->encode();

            return $content;
        }

        return $content;
    }

    public function getReplaceKeys($content)
    {
        preg_match_all('/(\$[^\s.]*?\$)/', $content, $matches);

        $keys = $matches[1] ?? [];

        return $keys;
    }

    public function getReplacesByKeys(array $keys)
    {
        $replaces = [];
        foreach ($keys as $key) {
            $currentReplacement = str_replace('$', '', $key);

            $currentReplacementLower = Str::of($currentReplacement)->lower()->toString();
            $method = sprintf('get%sReplacement', Str::studly($currentReplacementLower));

            if (method_exists($this, $method)) {
                $replaces[$currentReplacement] = $this->$method();
            } else {
                \info($currentReplacement.' does match any replace content');
                // keep origin content
                $replaces[$currentReplacement] = $key;
            }
        }

        return $replaces;
    }

    public function getReplacedContent(string $content, array $keys = [])
    {
        if (! $keys) {
            $keys = $this->getReplaceKeys($content);
        }

        $replaces = $this->getReplacesByKeys($keys);

        return str_replace($keys, $replaces, $content);
    }

    /**
     * Get array replacement for the specified stub.
     *
     * @param $stub
     * @return array
     */
    protected function getReplacement($stubPath)
    {
        if (! file_exists($stubPath)) {
            throw new \RuntimeException("stubPath $stubPath not exists");
        }

        $stubContent = @file_get_contents($stubPath);

        $keys = $this->getReplaceKeys($stubContent);

        $replaces = $this->getReplacesByKeys($keys);

        return $replaces;
    }

    public function getNamespaceReplacement()
    {
        return $this->getDefaultNamespace('App');
    }

    public function getClassReplacement()
    {
        return $this->getClass();
    }

    public function __get($name)
    {
        throw new \RuntimeException("unknown property $name");
    }
}
