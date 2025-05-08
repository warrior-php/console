<?php
declare(strict_types=1);

namespace Warrior\Console;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use support\Container;
use Symfony\Component\Console\Application;
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
     * Auto-load and register command classes
     *
     * @param string $path     命令类文件所在目录 / Directory containing command files
     * @param string $namspace 命名空间前缀 / Namespace prefix
     *
     * @return void
     */
    public function installCommands(string $path, string $namspace = 'App\Commands'): void
    {
        // 递归扫描目录
        // Recursively scan the directory
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            // 忽略以点号开头的文件（如 .DS_Store）
            // Skip hidden files (e.g., .DS_Store)
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            // 跳过非 PHP 文件
            // Skip non-PHP files
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // 获取文件相对于指定路径的类命名路径
            // Get relative class path from file path
            $relativePath = str_replace(
                str_replace('/', '\\', $path . '\\'),
                '',
                str_replace('/', '\\', $file->getRealPath())
            );

            // 生成实际命名空间（目录转换为命名空间）
            // Build the full namespace path
            $realNamespace = trim($namspace . '\\' . trim(dirname(str_replace('\\', DIRECTORY_SEPARATOR, $relativePath)), '.'), '\\');

            // 拼接完整类名（命名空间 + 类名）
            // Full class name = namespace + class name
            $class_name = trim($realNamespace . '\\' . $file->getBasename('.php'), '\\');

            // 若类不存在或不是 Symfony 命令子类，则跳过
            // Skip if class does not exist or is not a valid Symfony command
            if (!class_exists($class_name) || !is_a($class_name, Commands::class, true)) {
                continue;
            }

            // 注册命令到 Console 应用中（使用容器解析类）
            // Register the command using the container
            $this->add(Container::get($class_name));
        }
    }
}