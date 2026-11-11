<?php

/**
 * @package     Localzet Noter
 * @link        https://github.com/localzet/Noter
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 */

declare(strict_types=1);

use ZCONF\Builder;

require dirname(__DIR__) . '/vendor/autoload.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$binary = dirname(__DIR__) . '/build/noter.phar';
expect(is_file($binary), 'Build the Noter binary before running integration tests.');

$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
expect(is_resource($socket), 'Could not reserve a loopback port.');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$port = (int)substr(strrchr($address, ':'), 1);

$tempDir = sys_get_temp_dir() . '/noter-test-' . bin2hex(random_bytes(8));
mkdir($tempDir, 0700);
$tempBinary = $tempDir . '/noter';
$configPath = $tempDir . '/noter.zconf';
copy($binary, $tempBinary);
chmod($tempBinary, 0700);

$serverPipes = [];
$server = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/http_receiver.php'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
    $serverPipes
);
expect(is_resource($server), 'Could not start the loopback HTTP receiver.');

try {
    $ready = false;
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
        if (is_resource($probe)) {
            fclose($probe);
            $ready = true;
            break;
        }
        usleep(100_000);
    }
    expect($ready, 'Loopback HTTP receiver did not start.');

    $writeConfig = static function (string $url) use ($configPath): void {
        $config = (new Builder())
            ->addValue('NOTER_URL', $url)
            ->addValue('NOTER_KEY', 'integration-test-key')
            ->getString();
        file_put_contents($configPath, $config);
        chmod($configPath, 0600);
    };

    $writeConfig('http://notes.example.test/success');
    $pipes = [];
    $command = proc_open([PHP_BINARY, $tempBinary, 'must not send'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    expect(is_resource($command), 'Could not start Noter for the insecure URL case.');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($command);
    expect($status !== 0, 'Noter accepted a non-loopback HTTP URL.');
    expect(str_contains($stdout . $stderr, 'must use HTTPS'), 'Noter did not explain the HTTPS requirement.');

    $writeConfig("http://127.0.0.1:{$port}/success");
    $pipes = [];
    $command = proc_open([PHP_BINARY, $tempBinary, 'alpha <beta>'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    expect(is_resource($command), 'Could not start Noter.');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($command);
    expect($status === 0, 'Successful receiver request failed: ' . $stderr);
    expect(str_contains($stdout, 'integration-test-key'), 'Noter did not send the configured API key.');
    expect(str_contains($stdout, 'alpha &lt;beta&gt;'), 'Noter did not send the escaped note content.');

    $writeConfig("http://127.0.0.1:{$port}/failure");
    $command = proc_open([PHP_BINARY, $tempBinary, 'test'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    expect(is_resource($command), 'Could not start Noter for the HTTP failure case.');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($command);
    expect($status !== 0, 'Noter treated HTTP 503 as success.');
    expect(str_contains($stdout . $stderr, 'HTTP 503'), 'Noter did not report the HTTP failure status.');
} finally {
    proc_terminate($server);
    proc_close($server);
    unlink($tempBinary);
    unlink($configPath);
    rmdir($tempDir);
}

echo "Noter loopback integration checks passed.\n";
