<?php
declare(strict_types=1);

namespace Warrior\Console;

/**
 * 插件安装器类
 * 用于安装与卸载 Webman 控制台插件相关的文件与目录
 *
 * Class Install
 */
class Install
{
    /**
     * 是否为 Webman 插件
     * Flag to indicate this is a Webman plugin
     */
    const WEBMAN_PLUGIN = true;

    /**
     * 源路径与目标路径的映射关系
     * Path relations from plugin source to project destination
     *
     * @var array
     */
    protected static array $pathRelation = [
        'config' => [
            'path'  => 'config',
            'files' => ['console.php'],
        ],
    ];

    /**
     * 安装方法
     * 拷贝 webman 启动文件并安装相关配置目录
     *
     * @return void
     */
    public static function install(): void
    {
        // 拷贝 warrior 启动文件
        copy(__DIR__ . "/warrior", base_path() . "/warrior");
        chmod(base_path() . "/warrior", 0755);

        // 安装路径映射的文件
        static::installByRelation();
    }

    /**
     * 卸载方法
     * 删除 webman 启动文件和配置目录
     *
     * @return void
     */
    public static function uninstall(): void
    {
        // 删除 webman 启动文件
        if (is_file(base_path() . "/warrior")) {
            unlink(base_path() . "/warrior");
        }

        // 删除路径映射的文件
        self::uninstallByRelation();
    }

    /**
     * 按路径映射关系拷贝文件夹
     * Install directories/files based on pathRelation map
     *
     * @return void
     */
    public static function installByRelation(): void
    {
        foreach (static::$pathRelation as $source => $info) {
            $dest = $info['path'];

            // 如果目标路径包含目录结构，确保其存在
            if ($pos = strrpos($dest, '/')) {
                $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }

            // 执行目录复制
            copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
        }
    }

    /**
     * 按路径映射关系删除文件夹
     * Uninstall directories/files based on pathRelation map
     *
     * @return void
     */
    public static function uninstallByRelation(): void
    {
        foreach (static::$pathRelation as $info) {
            $destPath = base_path() . '/' . $info['path'];

            // 路径不是目录，跳过
            if (!is_dir($destPath)) {
                continue;
            }

            // 删除指定文件
            if (!empty($info['files'])) {
                foreach ($info['files'] as $file) {
                    $filePath = $destPath . '/' . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }
    }
}