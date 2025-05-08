<?php
declare(strict_types=1);

namespace Warrior\Console;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use support\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as Commands;

/**
 * 自定义命令管理器类，继承自 Symfony Console Application
 * Custom command loader extending Symfony Console Application
 */
class Command extends Application
{
    /**
     * 安装内部命令（从默认命令目录加载）
     * Install internal commands from predefined directory
     *
     * @return void
     */
    public function installInternalCommands(): void
    {
        $this->installCommands(__DIR__ . '/Commands', 'Warrior\Console\Commands');
    }

    /**
     * 自动加载并注册命令类
     *
     * @param        $path
     * @param string $namespace
     *
     * @return void
     */
    public function installCommands($path, string $namespace = 'App\Console'): void
    {
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // abc\def.php
            $relativePath = str_replace(str_replace('/', '\\', $path . '\\'), '', str_replace('/', '\\', $file->getRealPath()));
            // app\command\abc
            $realNamespace = trim($namespace . '\\' . trim(dirname(str_replace('\\', DIRECTORY_SEPARATOR, $relativePath)), '.'), '\\');
            $realNamespace = str_replace('/', '\\', $realNamespace);
            // app\command\doc\def
            $class_name = trim($realNamespace . '\\' . $file->getBasename('.php'), '\\');
            if (!class_exists($class_name) || !is_a($class_name, Commands::class, true)) {
                continue;
            }

            $this->createCommandInstance($class_name);
        }
    }

    /**
     * @param $class_name
     *
     * @return mixed|null
     */
    public function createCommandInstance($class_name): mixed
    {
        $reflection = new ReflectionClass($class_name);
        if ($reflection->isAbstract()) {
            return null;
        }

        $attributes = $reflection->getAttributes(AsCommand::class);
        if (!empty($attributes)) {
            $properties = current($attributes)->newInstance();
            $name = $properties->name;
            $description = $properties->description;
        } else {
            $properties = $reflection->getStaticProperties();
            $name = $properties['defaultName'] ?? null;
            if (!$name) {
                throw new RuntimeException("Command $class_name has no defaultName");
            }
            $description = $properties['defaultDescription'] ?? '';
        }
        $command = Container::get($class_name);
        $command->setName($name)->setDescription($description);
        $this->add($command);
        return $command;
    }
}