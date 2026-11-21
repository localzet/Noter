<?php

/**
 * @package     Localzet Noter
 * @link        https://github.com/localzet/Noter
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 */

$body = json_decode(file_get_contents('php://input'), true);
if (str_starts_with($_SERVER['REQUEST_URI'], '/failure')) {
    http_response_code(503);
    echo 'unavailable';
    return;
}

if (str_starts_with($_SERVER['REQUEST_URI'], '/large-response')) {
    echo str_repeat('x', 16 * 1024);
    return;
}

file_put_contents(
    (string)getenv('NOTER_TEST_CAPTURE_FILE'),
    json_encode([
        'key' => $_SERVER['HTTP_X_API_KEY'] ?? null,
        'content' => $body['content'] ?? null,
    ], JSON_THROW_ON_ERROR) . "\n",
    FILE_APPEND | LOCK_EX,
);

header('Content-Type: application/json');
echo "\033]52;c;receiver-response-secret\007\n";
