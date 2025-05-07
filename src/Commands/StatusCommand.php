<?php
declare(strict_types=1);

namespace Warrior\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Warrior\Console\Application;

use function class_exists;

/**
 * Class StatusCommand
 *
 * 控制台命令：用于获取 Worker 的运行状态。
 * 支持通过 --live 或 -d 参数实时查看运行状态。
 *
 * Console command to get the current status of the Worker.
 * Use the --live / -d option to show live status.
 *
 * @package Warrior\Console\Commands
 */
class StatusCommand extends Command
{
    /**
     * 命令名称：status
     *
     * @var string
     */
    protected static $defaultName = 'status';

    /**
     * 命令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Get worker status. Use mode -d to show live status.';

    /**
     * 配置命令参数和选项
     *
     * Defines CLI options for this command.
     *
     * @return void
     */
    protected function configure(): void
    {
        // 添加一个布尔型选项，用于控制是否显示实时状态
        $this->addOption(
            'live', // 长选项名 --live
            'd', // 短选项名 -d
            InputOption::VALUE_NONE, // 不带值，仅作开关
            'Show live status' // 参数说明
        );
    }

    /**
     * 执行命令逻辑
     *
     * 根据是否存在 Support\App 类，选择不同方式运行状态检查逻辑。
     *
     * @param InputInterface  $input  CLI 输入对象
     * @param OutputInterface $output CLI 输出对象
     *
     * @return int 返回状态码（0 表示成功）
     * @throws Throwable 抛出任何运行异常
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果存在用户定义的 App 运行类，则执行它
        if (class_exists(\Support\App::class)) {
            \Support\App::run();
            return self::SUCCESS;
        }

        // 否则执行默认 Application 的 run 方法
        Application::run();
        return self::SUCCESS;
    }
}