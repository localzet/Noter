#!/usr/bin/env php -d phar.readonly=0
<?php

/**
 * @package     Localzet Noter
 * @link        https://github.com/localzet/Noter
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <creator@localzet.com>
 */

use localzet\Console;
use Zorin\Noter\NoteCommand;

require_once './vendor/autoload.php';

$pharPath = class_exists(Phar::class, false)
    ? Phar::running(false)
    : null;

$installPath = Composer\InstalledVersions::getRootPackage()['install_path']
    ?? null;

define(
    'BASE_PATH',
    $pharPath
    ? dirname($pharPath)
    : (
        str_starts_with($installPath, 'phar://')
        ? $installPath
        : (
            realpath($installPath)
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
        'php_version' => 8.4,
        'custom_ini' => 'memory_limit = 256M',

        'bin_filename' => 'noter',
    ],
]);
$buildCommand = getenv('NOTER_BUILD_PHAR') === '1' ? 'build:phar' : 'build:bin';
$console->setDefaultCommand($buildCommand, true);
$console->run();
