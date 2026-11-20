# Contributing

[Русская версия](CONTRIBUTING_ru.md)

We welcome focused improvements to Noter. Please follow the [Code of Conduct](CODE_OF_CONDUCT.md) and keep changes aligned with the CLI and HTTP request contracts.

## Before opening a pull request

- Do not include real notes, API keys, credentials, private keys or production URLs in tests or examples.
- Describe the supported PHP versions and user-visible behavior affected by your change.
- Use the repository `.editorconfig` for PHP and YAML formatting.

## Local checks

```sh
composer install --no-interaction --prefer-dist
composer validate --strict
composer run lint
bash -n install.sh
composer run build:phar
composer run test
```

The integration test starts a temporary HTTP receiver on `127.0.0.1`; it does not contact a configured remote service.

## Pull requests

Use a short Conventional Commit title, explain the reason for the change, and list the commands you ran. Keep unrelated formatting and generated build artifacts out of the diff. Report security issues privately according to [SECURITY.md](SECURITY.md), not in a public issue.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
