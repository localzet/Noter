#!/usr/bin/env bash
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

output=$(php ./build/noter.phar --help)
if ! grep -q 'note' <<< "$output"; then
    printf 'Noter help output does not list the note command.\n' >&2
    exit 1
fi

# Keep PHP variables literal for the PHP interpreter.
# shellcheck disable=SC2016
php -r '$phar = new Phar("build/noter.phar"); if (!$phar->offsetExists("LICENSE")) { fwrite(STDERR, "PHAR does not contain the project license.\n"); exit(1); }'

if ! grep -Fq "releases/download/\${tag_version}/noter.phar" install.sh; then
    printf 'Installer download path does not match the release PHAR asset.\n' >&2
    exit 1
fi

if ! grep -Fq 'files: build/noter.phar' .github/workflows/release.yml; then
    printf 'Release workflow does not publish the installer PHAR asset.\n' >&2
    exit 1
fi

printf 'Noter CLI help smoke test passed.\n'
