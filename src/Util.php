<?php
declare(strict_types=1);

namespace Warrior\Console;

/**
 * 工具类
 * 提供名称与类名、命名空间之间的转换工具方法
 */
class Util
{
    /**
     * 将以连接符或斜杠分隔的字符串转换为命名空间格式
     *
     * 例如：foo-bar/baz → FooBar\Baz
     *
     * @param string $name 输入名称
     *
     * @return string 命名空间格式的字符串
     */
    public static function nameToNamespace(string $name): string
    {
        $namespace = ucfirst($name);
        $namespace = preg_replace_callback(['/-([a-zA-Z])/', '/(\/[a-zA-Z])/'], function ($matches) {
            return strtoupper($matches[1]);
        }, $namespace);

        return str_replace('/', '\\', ucfirst($namespace));
    }

    /**
     * 将类名转换为带下划线的名称形式（通常用于文件或命令名）
     *
     * 例如：FooBar → foo_bar
     *
     * @param string $class 类名
     *
     * @return string 转换后的名称
     */
    public static function classToName(string $class): string
    {
        $class = lcfirst($class);
        return preg_replace_callback(['/([A-Z])/'], function ($matches) {
            return '_' . strtolower($matches[1]);
        }, $class);
    }

    /**
     * 将连接符或下划线命名格式转换为类名格式
     *
     * 例如：foo_bar → FooBar，user/login → user/Login
     *
     * @param string $class 类名或路径
     *
     * @return string 转换后的类名（含路径）
     */
    public static function nameToClass(string $class): string
    {
        $class = preg_replace_callback(['/-([a-zA-Z])/', '/_([a-zA-Z])/'], function ($matches) {
            return strtoupper($matches[1]);
        }, $class);

        if (!($pos = strrpos($class, '/'))) {
            $class = ucfirst($class);
        } else {
            $path = substr($class, 0, $pos);
            $class = ucfirst(substr($class, $pos + 1));
            $class = "$path/$class";
        }

        return $class;
    }

    /**
     * 在指定目录下递归匹配实际路径（忽略大小写），用于路径智能解析
     *
     * @param string $base_path        根路径
     * @param string $name             要查找的路径（支持 foo/bar）
     * @param bool   $return_full_path 是否返回完整物理路径
     *
     * @return string|false 找到的相对路径或完整路径，失败返回 false
     */
    public static function guessPath(string $base_path, string $name, bool $return_full_path = false)
    {
        if (!is_dir($base_path)) {
            return false;
        }

        $names = explode('/', trim(strtolower($name), '/'));
        $realName = [];
        $path = $base_path;

        foreach ($names as $name) {
            $finded = false;
            foreach (scandir($path) ?: [] as $tmp_name) {
                if (strtolower($tmp_name) === $name && is_dir("$path/$tmp_name")) {
                    $path = "$path/$tmp_name";
                    $realName[] = $tmp_name;
                    $finded = true;
                    break;
                }
            }

            if (!$finded) {
                return false;
            }
        }

        $realName = implode(DIRECTORY_SEPARATOR, $realName);

        return $return_full_path ? get_realpath($base_path . DIRECTORY_SEPARATOR . $realName) : $realName;
    }
}