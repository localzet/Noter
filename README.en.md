<p align="center"><a href="https://www.localzet.com/" target="_blank">
  <img src="https://static.zorin.space/media/logos/ZorinProjectsSP.svg" alt="Localzet Group">
</a></p>

# Noter

English | [Русский](README.md)

Noter is a command-line client that sends a note to an HTTP(S) receiver configured by the operator.

- PHP 8.4.1 or later
- PHP cURL extension
- HTTPS for remote receivers

## Install

Download and review the installer before running it with elevated privileges:

```sh
curl -fsSLo /tmp/noter-install.sh --proto '=https' --tlsv1.2 https://raw.githubusercontent.com/localzet/Noter/main/install.sh
less /tmp/noter-install.sh
sudo bash /tmp/noter-install.sh -u "https://notes.example.com/api/notes"
```

The installer prompts for the API key without echoing it and writes the configuration under `/usr/local/noter/`. The `-k` option is available, but command-line arguments can be recorded in shell history or process listings.

## Use

```sh
noter "A note from the terminal"
printf '%s' "A note from standard input" | noter
```

The receiver's response is printed to standard output. Network failures and non-2xx HTTP responses return a failure exit code.

## Configuration

The installer creates `/usr/local/noter/noter.zconf`:

```ini
NOTER_URL = "https://notes.example.com/api/notes"
NOTER_KEY = "replace-with-your-api-key"
```

Noter also checks for `noter.zconf` next to the executable. Protect the file so only the account running Noter can read it.

Noter sends an HTTP `POST` request with `Content-Type: application/json`, an `X-API-KEY` header and a JSON `content` field. Remote URLs must use HTTPS. Plain HTTP is accepted only for loopback IP addresses such as `127.0.0.1` and `::1`.

The current client HTML-escapes note text before JSON encoding. For example, `<` is sent as `&lt;`. Account for this representation in the receiver; when displaying notes, use the receiver's normal output-encoding rules and do not insert untrusted content as raw HTML.

Noter is a client: it does not host the receiving API, store notes, or define the receiver's authorization and retention policy.

## Development

See [contribution guidelines](.github/CONTRIBUTING.md) and the [security policy](.github/SECURITY.md). PhpStorm uses the repository's `.editorconfig`; Visual Studio Code settings and tasks are in `.vscode/`.

Copyright © 2016-2026 [Localzet Group](https://www.localzet.com/). All rights reserved.
