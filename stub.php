<?php

use localzet\Console;
use Zorin\Noter\NoteCommand;

require_once __DIR__.'/vendor/autoload.php';

$pharPath = class_exists(Phar::class, false)
    ? Phar::running(false)
    : null;

$installPath = Composer\InstalledVersions::getRootPackage()['install_path']
    ?? null;

define('BASE_PATH', $pharPath
    ? dirname($pharPath)
    : (str_starts_with($installPath, 'phar://')
        ? $installPath
        : (realpath($installPath)
            ?? dirname(__DIR__)
        )
    )
);


$console = new Console([
    'build' => [
        'input_dir' => BASE_PATH,
        'output_dir' => BASE_PATH . DIRECTORY_SEPARATOR . 'build',

        'exclude_pattern' => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#',
        'exclude_files' => [
            'noter.zconf', 'LICENSE', 'composer.json', 'composer.lock', 'localzet.phar', 'localzet.bin', 'build.php'
        ],

        'phar_alias' => 'noter',
        'phar_filename' => 'noter.phar',
        'phar_stub' => 'stub.php',

        'signature_algorithm' => Phar::SHA256, // Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, Phar::OPENSSL.
        'php_version' => 8.2,
        'custom_ini' => 'memory_limit = 256M',

        'bin_filename' => 'noter',
    ],
]);
$console->add(new NoteCommand);
$console->setDefaultCommand('note', true);
$console->run();