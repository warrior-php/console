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
 * Class StartCommand
 *
 * 控制台命令：用于启动 Worker 进程（开发模式或守护进程模式）。
 * 支持通过 --daemon 或 -d 参数以守护进程模式运行。
 *
 * Console command to start the Worker in DEBUG or DAEMON mode.
 *
 * @package Warrior\Console\Commands
 */
class StartCommand extends Command
{
    /**
     * 命令名称：start
     *
     * @var string
     */
    protected static string $defaultName = 'start';

    /**
     * 命令描述
     *
     * @var string
     */
    protected static string $defaultDescription = 'Start worker in DEBUG mode. Use mode -d to start in DAEMON mode.';

    /**
     * 配置命令参数和选项
     *
     * Defines available CLI options for this command.
     *
     * @return void
     */
    protected function configure(): void
    {
        // 添加一个名为 daemon（简写 d）的布尔选项，无需传值
        $this->addOption(
            'daemon', // 长选项名 --daemon
            'd', // 短选项名 -d
            InputOption::VALUE_NONE, // 不需要传值，仅作为开关
            'DAEMON mode' // 选项说明
        );
    }

    /**
     * 执行命令逻辑
     *
     * 根据是否存在 Support\App 类来启动对应的应用程序逻辑。
     *
     * @param InputInterface  $input  CLI 输入对象
     * @param OutputInterface $output CLI 输出对象
     *
     * @return int 返回状态码（0 表示成功）
     * @throws Throwable 抛出任何可能发生的异常
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果存在 Support\App 类，则优先运行该类的 run 方法（可能是用户自定义启动流程）
        if (class_exists(App::class)) {
            App::run();
            return self::SUCCESS;
        }

        // 否则运行默认的 Application 类启动流程
        Application::run();

        return self::SUCCESS;
    }
}