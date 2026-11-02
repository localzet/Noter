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

header('Content-Type: application/json');
echo json_encode([
    'key' => $_SERVER['HTTP_X_API_KEY'] ?? null,
    'content' => $body['content'] ?? null,
]);
