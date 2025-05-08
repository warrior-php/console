<?php

namespace Warrior\Console;

use Phar;
use RuntimeException;
use support\Container;
use support\Log;
use support\Request;
use Webman\App;
use Webman\Config;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;
use Dotenv\Dotenv;
use const DIRECTORY_SEPARATOR;

// 开启错误显示
// Turn on error displaying
ini_set('display_errors', 'on');
error_reporting(E_ALL);

/**
 * 应用启动类
 * Application bootstrap class
 */
class Application
{
    /**
     * 启动整个应用
     * Run the application
     *
     * @return void
     */
    public static function run(): void
    {
        // 设置运行时日志目录
        // Setup runtime log directory
        $runtime_logs_path = runtime_path() . DIRECTORY_SEPARATOR . 'logs';

        // 如果日志目录不存在，则创建
        // Create log directory if not exists
        if (!file_exists($runtime_logs_path) || !is_dir($runtime_logs_path)) {
            if (!mkdir($runtime_logs_path, 0777, true)) {
                throw new RuntimeException("无法创建运行日志目录，请检查权限 / Failed to create runtime logs directory. Please check the permission.");
            }
        }

        // 设置运行时视图目录
        // Setup runtime views directory
        $runtime_views_path = runtime_path() . DIRECTORY_SEPARATOR . 'views';

        if (!file_exists($runtime_views_path) || !is_dir($runtime_views_path)) {
            if (!mkdir($runtime_views_path, 0777, true)) {
                throw new RuntimeException("无法创建运行视图目录，请检查权限 / Failed to create runtime views directory. Please check the permission.");
            }
        }

        // 加载 .env 环境变量（优先使用安全方法）
        // Load environment variables from .env file
        if (class_exists('Dotenv\Dotenv') && file_exists(base_path() . '/.env')) {
            if (method_exists('Dotenv\Dotenv', 'createUnsafeImmutable')) {
                Dotenv::createUnsafeImmutable(base_path())->load();
            } else {
                Dotenv::createMutable(base_path())->load();
            }
        }

        // 重新加载配置文件，忽略 route 与 container
        // Reload config files (excluding route & container)
        Config::reload(config_path(), ['route', 'container']);

        // 设置主进程重启时的回调：清除 opcache 缓存
        // Set callback for master reload to clear opcache
        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

        // 获取服务器配置
        // Load server config
        $config = config('server');

        // 设置 Workerman 全局参数
        Worker::$pidFile = $config['pid_file'];
        Worker::$stdoutFile = $config['stdout_file'];
        Worker::$logFile = $config['log_file'];
        Worker::$eventLoopClass = $config['event_loop'] ?? '';
        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;

        if (property_exists(Worker::class, 'statusFile')) {
            Worker::$statusFile = $config['status_file'] ?? '';
        }

        if (property_exists(Worker::class, 'stopTimeout')) {
            Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
        }

        // 如果配置了监听地址，则初始化主服务 Worker
        // If listen address is defined, create a main worker
        if ($config['listen']) {
            $worker = new Worker($config['listen'], $config['context']);

            // 映射并设置 Worker 配置项
            $property_map = [
                'name',
                'count',
                'user',
                'group',
                'reusePort',
                'transport',
                'protocol'
            ];

            foreach ($property_map as $property) {
                if (isset($config[$property])) {
                    $worker->$property = $config[$property];
                }
            }

            // 设置 Worker 启动时的回调函数
            // Setup worker on start handler
            $worker->onWorkerStart = function ($worker) {
                require_once base_path() . '/support/bootstrap.php';

                // 实例化 Webman 应用对象
                $app = new App(
                    $worker,
                    Container::instance(),
                    Log::channel('default'),
                    app_path(),
                    public_path()
                );

                // 设置 HTTP 请求类
                Http::requestClass(config('app.request_class', config('server.request_class', Request::class)));

                // 设置消息回调处理
                $worker->onMessage = [$app, 'onMessage'];
            };
        }

        // Windows 系统不支持自定义进程，仅在类 Unix 系统下执行
        // Windows doesn't support custom processes
        if (DIRECTORY_SEPARATOR === '/') {
            // 启动自定义进程（排除 monitor 进程）
            foreach (config('process', []) as $process_name => $config) {
                if (class_exists(Phar::class, false) && Phar::running() && $process_name === 'monitor') {
                    continue;
                }
                worker_start($process_name, $config);
            }

            // 启动插件自定义进程
            foreach (config('plugin', []) as $firm => $projects) {
                foreach ($projects as $name => $project) {
                    foreach ($project['process'] ?? [] as $process_name => $config) {
                        worker_start("plugin.$firm.$name.$process_name", $config);
                    }
                }
            }
        }

        // 启动所有 Worker 实例
        // Run all workers
        Worker::runAll();
    }
}