#!/usr/bin/env bash
# Copyright © 2016-2026 Localzet Group. All rights reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

output=$(php ./build/noter.phar --help)
if ! grep -q 'note' <<< "$output"; then
    printf 'Noter help output does not list the note command.\n' >&2
    exit 1
fi

php -r '$phar = new Phar("build/noter.phar"); if (!$phar->offsetExists("LICENSE")) { fwrite(STDERR, "PHAR does not contain the project license.\n"); exit(1); }'

printf 'Noter CLI help smoke test passed.\n'
