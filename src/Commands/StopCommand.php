<?php
declare(strict_types=1);

namespace Warrior\Console\Commands;

use Support\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Warrior\Console\Application;

use function class_exists;

/**
 * Class StopCommand
 *
 * 控制台命令：用于停止 Worker 服务。
 * 支持通过 --graceful 或 -g 以“优雅停止”模式终止 Worker。
 *
 * Console command to stop Worker processes.
 * Supports graceful shutdown using --graceful or -g flag.
 *
 * @package Warrior\Console\Commands
 */
class StopCommand extends Command
{
    /**
     * 命令名称：stop
     *
     * @var string
     */
    protected static string $defaultName = 'stop';

    /**
     * 命令描述
     *
     * @var string
     */
    protected static string $defaultDescription = 'Stop worker. Use mode -g to stop gracefully.';

    /**
     * 配置命令选项
     *
     * Defines CLI options for the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        // 添加 --graceful / -g 选项，用于指定是否优雅停止
        $this->addOption(
            'graceful', // 长选项名
            'g', // 短选项名
            InputOption::VALUE_NONE, // 不带参数的布尔值选项
            'Graceful stop' // 选项说明
        );
    }

    /**
     * 执行命令主逻辑
     *
     * 判断是否存在自定义启动类 Support\App，并调用其 run() 方法。
     * 否则使用默认的 Application::run() 启动逻辑。
     *
     * 实际的停止逻辑由 Support\App 或 Application 实现。
     *
     * @param InputInterface  $input  命令行输入对象
     * @param OutputInterface $output 命令行输出对象
     *
     * @return int 返回状态码（0 表示成功）
     * @throws Throwable 若执行中发生异常将抛出
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果用户定义的 Support\App 类存在，调用其 run 方法
        if (class_exists(App::class)) {
            App::run();
            return self::SUCCESS;
        }

        // 否则使用默认 Application 执行停止逻辑
        Application::run();
        return self::SUCCESS;
    }
}