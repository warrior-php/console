<?php
declare(strict_types=1);

/**
 * 打包配置文件（Phar 打包相关设置）
 *
 * Configuration for Phar package building.
 */

return [

    /**
     * 是否启用打包功能
     * Enable or disable the build process.
     */
    'enable'              => true,

    /**
     * 打包输出目录（绝对路径）
     * Directory where the generated Phar and bin files will be saved.
     */
    'build_dir'           => BASE_PATH . DIRECTORY_SEPARATOR . 'build',

    /**
     * 生成的 Phar 包文件名
     * The filename of the output Phar archive.
     */
    'phar_filename'       => 'warrior.phar',

    /**
     * 生成的二进制文件名（启动脚本）
     * The filename of the generated binary wrapper script.
     */
    'bin_filename'        => 'warrior.bin',

    /**
     * 签名算法（用于验证 Phar 包完整性）
     * The algorithm used to sign the Phar archive.
     * Must be one of: Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, Phar::OPENSSL
     */
    'signature_algorithm' => Phar::SHA256,

    /**
     * 私钥文件路径（用于 OPENSSL 签名，仅当使用 Phar::OPENSSL 时生效）
     * The file path to a private key or certificate used when Phar::OPENSSL is selected.
     */
    'private_key_file'    => '',

    /**
     * 文件/目录排除正则模式
     * A regular expression pattern used to exclude files or directories from being packed.
     *
     * 默认排除：
     * - 配置文件（composer.json）
     * - GitHub、IDE 和 Git 目录（.github, .idea, .git）
     * - 本地缓存和运行时文件夹（runtime, vendor-bin, build）
     */
    'exclude_pattern'     => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#',

    /**
     * 需显式排除的文件列表（与 exclude_pattern 配合）
     * Explicit list of files to be excluded from the archive.
     */
    'exclude_files'       => [
        '.env',
        'LICENSE',
        'composer.json',
        'composer.lock',
        'start.php',
        'warrior.phar',
        'warrior.bin',
    ]
];