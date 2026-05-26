# TLS certificate bundle

`cacert.pem` is the Mozilla CA bundle used for outbound HTTPS (URL crawl, OpenAI API, webhooks).

If you see **cURL error 60: unable to get local issuer certificate** on Windows:

1. Ensure this file exists (re-download from https://curl.se/ca/cacert.pem if missing).
2. The app applies it automatically via `config/chatbot.php` → `http.ca_bundle`.
3. For CLI, `php-cli.ini` may also point `curl.cainfo` and `openssl.cafile` here.

Do not set `HTTP_SSL_VERIFY=false` in production.
