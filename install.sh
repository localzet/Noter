#!/bin/bash

red='\033[0;31m'
green='\033[0;32m'
yellow='\033[0;33m'
plain='\033[0m'

function LOGE() {
    echo -e "${red}$*${plain}"
}

function LOGI() {
    echo -e "${green}$*${plain}"
}

while getopts "k:u:" opt; do
  case $opt in
    k)
      key=$OPTARG
      LOGI "Key: $key"
      ;;
    u)
      url=$OPTARG
      LOGI "URL: $url"
      ;;
    *)
      LOGE "Invalid option"
      exit 1
      ;;
  esac
done

# Проверка root-прав
[[ $EUID -ne 0 ]] && LOGE "Ошибка: Пожалуйста, запустите скрипт с root-правами!" && exit 1

LOGI "Запуск..."

# Получение последней версии
tag_version=$(curl -Ls "https://api.github.com/repos/localzet/noter/releases/latest" | grep '"tag_name":' | sed -E 's/.*"([^"]+)".*/\1/')
if [[ ! -n "$tag_version" ]]; then
    LOGE "Ошибка получения версии noter, возможно, это связано с ограничениями API Github, попробуйте позже"
    exit 1
fi

LOGI "Получена версия noter: ${tag_version}, запуск установки..."
wget -N --no-check-certificate -O /usr/local/bin/noter "https://github.com/localzet/noter/releases/download/${tag_version}/noter"
if [[ $? -ne 0 ]]; then
    LOGE "Ошибка загрузки noter, пожалуйста, убедитесь, что ваш сервер имеет доступ к Github"
    exit 1
fi

chmod +x /usr/local/bin/noter

mkdir -p /usr/local/noter

config_file="/usr/local/noter/noter.zconf"

LOGI "Запись конфигурации..."
LOGI "NOTER_URL: ${url:-\"\"}"
LOGI "NOTER_KEY: ${key:-\"\"}"

if [ -n "$url" ]; then
  echo "NOTER_URL = \"$url\"" > $config_file
else
  echo "NOTER_URL = \"\"" > $config_file
fi

if [ -n "$key" ]; then
  echo "NOTER_KEY = \"$key\"" >> $config_file
else
  echo "NOTER_KEY = \"\"" >> $config_file
fi

LOGI "Файл конфигурации $config_file создан."

LOGI "Установка завершена..."
