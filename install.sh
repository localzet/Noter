#!/usr/bin/env bash
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail
umask 077

red='\033[0;31m'
green='\033[0;32m'
plain='\033[0m'

LOGE() { echo -e "${red}$*${plain}" >&2; }
LOGI() { echo -e "${green}$*${plain}"; }

url=''
key=''
while getopts 'k:u:' opt; do
    case "$opt" in
        k) key=$OPTARG ;;
        u) url=$OPTARG ;;
        *) LOGE 'Invalid option'; exit 1 ;;
    esac
done

if (( EUID != 0 )); then
    LOGE 'Please run this installer with root privileges.'
    exit 1
fi

if [[ -z "$url" ]]; then
    LOGE 'The -u (server URL) option is required.'
    exit 1
fi
if [[ -z "$key" && -t 0 ]]; then
    read -r -s -p 'API key: ' key
    printf '\n'
fi
if [[ -z "$key" ]]; then
    LOGE 'Provide an API key with -k or enter it at the prompt.'
    exit 1
fi

escape_zconf() {
    local value=$1
    value=${value//\\/\\\\}
    value=${value//\"/\\\"}
    value=${value//$'\b'/\\b}
    value=${value//$'\t'/\\t}
    value=${value//$'\n'/\\n}
    value=${value//$'\f'/\\f}
    value=${value//$'\r'/\\r}
    printf '%s' "$value"
}

LOGI 'Checking the latest Noter release...'
release_json=$(curl --fail --silent --show-error --location \
    --proto '=https' --tlsv1.2 --connect-timeout 10 --max-time 30 \
    'https://api.github.com/repos/localzet/noter/releases/latest') || {
    LOGE 'Could not retrieve the latest Noter release.'
    exit 1
}
tag_version=$(printf '%s' "$release_json" | grep '"tag_name":' | head -1 | sed -E 's/.*"([^"]+)".*/\1/')
if [[ ! "$tag_version" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    LOGE 'The release API returned an invalid version tag.'
    exit 1
fi

LOGI "Downloading Noter ${tag_version}..."
install_path='/usr/local/bin/noter'
download_path=$(mktemp '/usr/local/bin/.noter.download.XXXXXX')
if ! curl --fail --silent --show-error --location \
    --proto '=https' --tlsv1.2 --connect-timeout 10 --max-time 120 \
    --output "$download_path" \
    "https://github.com/localzet/noter/releases/download/${tag_version}/noter"; then
    LOGE 'Could not download Noter.'
    exit 1
fi
chmod 755 "$download_path"
mv -fT "$download_path" "$install_path"

config_dir='/usr/local/noter'
config_file="${config_dir}/noter.zconf"
if [[ -L "$config_dir" ]]; then
    LOGE 'The configuration directory must not be a symbolic link.'
    exit 1
fi
mkdir -p "$config_dir"
chmod 700 "$config_dir"
config_tmp=$(mktemp "${config_dir}/.noter.zconf.XXXXXX")
printf 'NOTER_URL = "%s"\nNOTER_KEY = "%s"\n' \
    "$(escape_zconf "$url")" "$(escape_zconf "$key")" > "$config_tmp"
chmod 600 "$config_tmp"
mv -fT "$config_tmp" "$config_file"

LOGI "Noter ${tag_version} installed. Configuration saved with restricted permissions."
