<?php

use localzet\Console;
use Zorin\Noter\NoteCommand;

require_once './vendor/autoload.php';

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


$console = new Console();
$console->add(new NoteCommand);
$console->setDefaultCommand('note', true);
$console->run();