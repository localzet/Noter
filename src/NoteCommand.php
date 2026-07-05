<?php

namespace Zorin\Noter;

use http\Exception\RuntimeException;
use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZCONF\Parser;

class NoteCommand extends Command
{
    protected static string $defaultName = 'note';
    protected array $config = [];

    protected function configure()
    {
        $this->addArgument('content', InputArgument::OPTIONAL, 'The content of the note.');

        if (file_exists(BASE_PATH . '/noter.zconf')) {
            $this->config = Parser::parseFile(BASE_PATH . '/noter.zconf');
        } elseif (file_exists('/usr/local/noter/noter.zconf')) {
            $this->config = Parser::parseFile('/usr/local/noter/noter.zconf');
        }
        if (empty($this->config) || !isset($this->config['NOTER_URL']) || !isset($this->config['NOTER_KEY'])) {
            throw new RuntimeException('Please set NOTER_URL or NOTER_KEY');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $content = $input->getArgument('content');
        if (!$content) {
            $content = file_get_contents('php://stdin');
        }

        $url = $this->config['NOTER_URL'];
        $apiKey = $this->config['NOTER_KEY'];

        // Валидация контента
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Инициализация cURL
        $ch = curl_init($url);

        // Параметры запроса
        $postData = json_encode(['content' => $content]);
        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $apiKey,
        ];

        // Настройки cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Выполнение запроса
        $response = curl_exec($ch);

        // Проверка на ошибки
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $output->writeln('Ошибка cURL: ' . $error);
            return Command::FAILURE;
        }

        curl_close($ch);

        // Логирование и вывод ответа
        $output->writeln('Response: ' . $response);

        return Command::SUCCESS;
    }
}
