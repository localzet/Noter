# Security policy

[Русская версия](SECURITY_ru.md)

## Supported versions

Security reports are assessed against the current default branch and the latest published release. No backport schedule or response-time SLA is published; fixes for older releases are evaluated case by case.

## Reporting a vulnerability

Report suspected vulnerabilities privately to **Ivan Zorin** at <creator@localzet.com>. Do not disclose exploit details, credentials, API keys, note content or production traffic in a public issue or pull request.

When safe, include the affected Noter version or commit, PHP and operating-system versions, relevant configuration with secrets removed, impact, and a minimal reproduction using synthetic notes and a local receiver.

## Security boundary

Noter sends note content and the configured API key to the endpoint selected in `NOTER_URL`. Use HTTPS for remote endpoints. Plain HTTP is accepted only for loopback IP addresses. Protect `noter.zconf` with restrictive file permissions, rotate exposed API keys, and make the receiving application enforce authorization and safely encode note content when rendering it.

A passing test suite does not certify the security of a receiving service or deployment.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
