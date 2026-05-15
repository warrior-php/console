<?php
declare(strict_types=1);

namespace Warrior\Console\Commands;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webman\Route;

/**
 * Class RouteListCommand
 *
 * 自定义控制台命令，用于输出 Webman 框架中所有定义的路由信息。
 * 支持展示路由的 URI、请求方法、回调方式、中间件及名称等内容。
 *
 * @package Warrior\Console\Commands
 */
class RouteListCommand extends Command
{
    /**
     * 命令名称（用于 CLI 调用，例如：php cli.php route:list）
     *
     * @var string
     */
    protected static string $defaultName = 'route:list';

    /**
     * 命令描述（用于在帮助信息中显示）
     *
     * @var string
     */
    protected static string $defaultDescription = 'Route list';

    /**
     * 执行命令
     *
     * 本方法在调用命令时被自动执行。
     * 其作用是收集所有已注册的路由信息并以表格形式输出。
     *
     * @param InputInterface  $input  输入接口（用于接收 CLI 参数）
     * @param OutputInterface $output 输出接口（用于输出结果到终端）
     *
     * @return int 返回执行状态码（0 表示成功）
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 表格头部字段定义
        $headers = ['uri', 'method', 'callback', 'middleware', 'name'];

        // 用于存储路由信息的表格内容
        $rows = [];

        // 获取所有路由
        foreach (Route::getRoutes() as $route) {
            // 获取每个路由所支持的请求方法（如 GET、POST 等）
            foreach ($route->getMethods() as $method) {
                // 获取路由回调处理函数
                $cb = $route->getCallback();

                // 格式化回调函数的信息
                if ($cb instanceof Closure) {
                    $cb = 'Closure'; // 匿名函数
                } elseif (is_array($cb)) {
                    // 数组形式通常是控制器类方法
                    $cb = json_encode($cb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // 其他格式（如字符串）
                    $cb = var_export($cb, true);
                }

                // 将路由信息填入表格行中
                $rows[] = [
                    $route->getPath(), // 路由 URI
                    $method, // 请求方法
                    $cb, // 回调函数
                    json_encode($route->getMiddleware() ?: null), // 中间件列表
                    $route->getName(), // 路由名称（可选）
                ];
            }
        }

        // 创建并渲染表格输出
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();

        // 返回成功状态码
        return self::SUCCESS;
    }
}