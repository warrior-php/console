<?php
declare(strict_types=1);

namespace Warrior\Console\Commands;

use Closure;
use Exception;
use ReflectionClass;
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
                    $cbDisplay = 'Closure'; // 匿名函数
                } elseif (is_array($cb)) {
                    // 数组形式通常是控制器类方法
                    $cbDisplay = json_encode($cb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // 其他格式（如字符串）
                    $cbDisplay = var_export($cb, true);
                }

                // 收集中间件（包括注解中间件）
                $middlewares = $this->collectMiddlewares($route, $cb);

                // 将路由信息填入表格行中
                $rows[] = [
                    $route->getPath(), // 路由 URI
                    $method, // 请求方法
                    $cbDisplay, // 回调函数
                    json_encode($middlewares ?: null, JSON_UNESCAPED_UNICODE), // 中间件列表
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

    /**
     * 收集中间件信息（包括路由设置的和注解中的）
     *
     * @param object $route    路由对象
     * @param mixed  $callback 路由回调
     *
     * @return array 中间件数组
     */
    private function collectMiddlewares(object $route, mixed $callback): array
    {
        $middlewares = [];

        // 1. 获取路由直接设置的中间件
        $routeMiddlewares = $route->getMiddleware();
        if (!empty($routeMiddlewares)) {
            $middlewares = array_merge($middlewares, $routeMiddlewares);
        }

        // 2. 从注解中收集中间件
        $annotationMiddlewares = $this->getAnnotationMiddlewares($callback);
        if (!empty($annotationMiddlewares)) {
            $middlewares = array_merge($middlewares, $annotationMiddlewares);
        }

        // 去重
        return array_unique($middlewares);
    }

    /**
     * 从注解中获取中间件
     *
     * @param mixed $callback 路由回调
     *
     * @return array 中间件数组
     */
    private function getAnnotationMiddlewares(mixed $callback): array
    {
        $middlewares = [];

        // 解析控制器类名
        $controllerClass = null;

        if (is_array($callback) && count($callback) === 2) {
            [$controllerClass,] = $callback;
        } elseif (is_string($callback)) {
            if (str_contains($callback, '@')) {
                [$controllerClass,] = explode('@', $callback, 2);
            } elseif (class_exists($callback)) {
                $controllerClass = $callback;
            }
        }

        // 如果无法解析控制器类，返回空数组
        if (!$controllerClass || !is_string($controllerClass) || !class_exists($controllerClass)) {
            return [];
        }

        try {
            // 遍历类继承链，收集所有 Middleware 注解
            $reflectionClass = new ReflectionClass($controllerClass);
            $currentClass = $reflectionClass;

            while ($currentClass) {
                // 检查当前类的所有属性
                $attributes = $currentClass->getAttributes();

                foreach ($attributes as $attribute) {
                    // 只处理 Middleware 注解
                    if ($attribute->getName() === 'support\annotation\Middleware') {
                        $arguments = $attribute->getArguments();

                        // 直接从参数中提取中间件类名
                        foreach ($arguments as $arg) {
                            if (is_string($arg)) {
                                $middlewares[] = $arg;
                            } elseif (is_array($arg)) {
                                foreach ($arg as $item) {
                                    if (is_string($item)) {
                                        $middlewares[] = $item;
                                    }
                                }
                            }
                        }
                    }
                }

                // 继续检查父类
                $currentClass = $currentClass->getParentClass();
            }
        } catch (Exception $e) {
            // 忽略异常，避免影响命令执行
        }

        return $middlewares;
    }
}
