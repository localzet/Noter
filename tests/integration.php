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
$capturePath = $tempDir . '/requests.jsonl';
file_put_contents($capturePath, '');
copy($binary, $tempBinary);
chmod($tempBinary, 0700);

$serverPipes = [];
$server = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/http_receiver.php'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
    $serverPipes,
    null,
    ['NOTER_TEST_CAPTURE_FILE' => $capturePath],
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

    $runNoter = static function (array $arguments, ?string $stdin = null) use ($tempBinary): array {
        $pipes = [];
        $command = proc_open(
            [PHP_BINARY, $tempBinary, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        expect(is_resource($command), 'Could not start Noter.');
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($command), $stdout, $stderr];
    };

    $latestRequest = static function () use ($capturePath): array {
        $lines = file($capturePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $line = is_array($lines) ? end($lines) : false;
        expect(is_string($line), 'The receiver did not capture a request.');
        $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        expect(is_array($request), 'The receiver captured an invalid request.');

        return $request;
    };

    $writeConfig('http://notes.example.test/success');
    [$status, $stdout, $stderr] = $runNoter(['must not send']);
    expect($status !== 0, 'Noter accepted a non-loopback HTTP URL.');
    expect(str_contains($stdout . $stderr, 'must use HTTPS'), 'Noter did not explain the HTTPS requirement.');

    $writeConfig("http://127.0.0.1:{$port}/success");
    [$status, $stdout, $stderr] = $runNoter(['alpha <beta> & gamma']);
    expect($status === 0, 'Successful receiver request failed: ' . $stderr);
    expect(str_contains($stdout, 'HTTP 200'), 'Noter did not report a successful HTTP status.');
    expect(!str_contains($stdout . $stderr, 'integration-test-key'), 'Noter printed the configured API key.');
    expect(!str_contains($stdout . $stderr, 'alpha <beta> & gamma'), 'Noter printed the note content.');
    expect(!str_contains($stdout . $stderr, "\033]52;"), 'Noter printed an OSC terminal control sequence.');
    $request = $latestRequest();
    expect(($request['key'] ?? null) === 'integration-test-key', 'Noter did not send the configured API key.');
    expect(($request['content'] ?? null) === 'alpha <beta> & gamma', 'Noter did not send the original note content.');

    [$status, $stdout, $stderr] = $runNoter(['0'], 'stdin must not replace this argument');
    expect($status === 0, 'Note content `0` was rejected: ' . $stderr);
    expect(($latestRequest()['content'] ?? null) === '0', 'Note content `0` was replaced with stdin.');

    [$status, $stdout, $stderr] = $runNoter([''], "content from empty argument stdin\n");
    expect($status === 0, 'Noter failed to read stdin for empty content: ' . $stderr);
    expect(($latestRequest()['content'] ?? null) === "content from empty argument stdin\n", 'An empty content argument no longer reads stdin.');

    [$status, $stdout, $stderr] = $runNoter([], "content from stdin\n");
    expect($status === 0, 'Noter failed to read stdin: ' . $stderr);
    expect(($latestRequest()['content'] ?? null) === "content from stdin\n", 'Noter changed stdin content.');

    $writeConfig("http://127.0.0.1:{$port}/large-response");
    [$status, $stdout, $stderr] = $runNoter(['test']);
    expect($status !== 0, 'Noter accepted an oversized response body.');
    expect(str_contains($stdout . $stderr, '8 KiB limit'), 'Noter did not report the response-size limit.');
    expect(strlen($stdout . $stderr) < 1024, 'Noter printed an oversized response body.');

    $writeConfig("http://127.0.0.1:{$port}/failure");
    [$status, $stdout, $stderr] = $runNoter(['test']);
    expect($status !== 0, 'Noter treated HTTP 503 as success.');
    expect(str_contains($stdout . $stderr, 'HTTP 503'), 'Noter did not report the HTTP failure status.');
} finally {
    proc_terminate($server);
    proc_close($server);
    unlink($tempBinary);
    unlink($configPath);
    unlink($capturePath);
    rmdir($tempDir);
}

echo "Noter loopback integration checks passed.\n";
