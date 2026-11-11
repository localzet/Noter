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

namespace Zorin\Noter;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZCONF\Parser;

class NoteCommand extends Command
{
    protected static string $defaultName = 'note';
    protected array $config = [];

    protected function configure(): void
    {
        $this->addArgument('content', InputArgument::OPTIONAL, 'The content of the note.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loadConfig();

        $content = $input->getArgument('content');
        if (!$content) {
            $content = file_get_contents('php://stdin');
        }

        if (!is_string($content)) {
            $output->writeln('Unable to read note content.');
            return Command::FAILURE;
        }

        $url = $this->config['NOTER_URL'];
        $apiKey = $this->config['NOTER_KEY'];
        if (!is_string($url) || !is_string($apiKey)) {
            $output->writeln('NOTER_URL and NOTER_KEY must be strings.');
            return Command::FAILURE;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);
        $packedHost = is_string($host) ? inet_pton(trim($host, '[]')) : false;
        $isLoopback = $packedHost !== false && (
            (strlen($packedHost) === 4 && ord($packedHost[0]) === 127)
            || $packedHost === inet_pton('::1')
        );
        if (
            !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
            || ($scheme === 'http' && !$isLoopback)
        ) {
            $output->writeln('NOTER_URL must use HTTPS, or HTTP with a loopback IP address.');
            return Command::FAILURE;
        }
        if (str_contains($apiKey, "\r") || str_contains($apiKey, "\n")) {
            $output->writeln('NOTER_KEY must not contain line breaks.');
            return Command::FAILURE;
        }

        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $ch = curl_init($url);
        if ($ch === false) {
            $output->writeln('Unable to initialize the HTTP request.');
            return Command::FAILURE;
        }

        try {
            $postData = json_encode(['content' => $content], JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            curl_close($ch);
            $output->writeln('Note content is not valid UTF-8.');
            return Command::FAILURE;
        }
        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $apiKey,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            curl_close($ch);
            $output->writeln('The note request failed. Check the URL and network connection.');
            return Command::FAILURE;
        }
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            $output->writeln(sprintf('The note server returned HTTP %d.', $status));
            return Command::FAILURE;
        }

        $output->writeln('Response: ' . $response);

        return Command::SUCCESS;
    }

    private function loadConfig(): void
    {
        if (file_exists(BASE_PATH . '/noter.zconf')) {
            $this->config = Parser::parseFile(BASE_PATH . '/noter.zconf') ?? [];
        } elseif (file_exists('/usr/local/noter/noter.zconf')) {
            $this->config = Parser::parseFile('/usr/local/noter/noter.zconf') ?? [];
        }

        if (
            empty($this->config['NOTER_URL'])
            || !isset($this->config['NOTER_KEY'])
            || $this->config['NOTER_KEY'] === ''
        ) {
            throw new \RuntimeException('Please set NOTER_URL and NOTER_KEY.');
        }
    }
}
