<?php

class PomeloChecker
{
    private \PDO $connection;

    public function __construct()
    {
        $this->connection = DbConnection::getInstance()->getConnection();
    }

    public function send_request_with_rate_limit(string $url, array $headers, array $body): string|bool|float
    {
        $cache_key = md5($url . json_encode($headers) . json_encode($body));
        $cache_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate_limit_cache';

        if (!file_exists($cache_dir) && !mkdir($cache_dir)) {
            return false;
        }

        $cache_file = $cache_dir . DIRECTORY_SEPARATOR . $cache_key . '.json';

        if (file_exists($cache_file)) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            $remaining = $cached_data['remaining'];
            $reset_after = $cached_data['reset_after'];
        } else {
            $remaining = null;
            $reset_after = null;
        }

        if ($reset_after == null || $reset_after < 0 || $reset_after == false || $reset_after < time()) {
            $reset_after = null;
            $remaining = 5;
        }

        if ($remaining === 0 && $reset_after !== null) {
            return $reset_after - time();
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $response = substr($response, $header_size);
        $http_response_header = explode("\r\n", trim($header_string));

        header('Status: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE), true);

        curl_close($ch);

        if ($http_code == 429) {
            $remaining = 0;
            $reset_after = (float) explode(': ', $http_response_header[4])[1];

            $cache_data = array(
                'remaining' => $remaining,
                'reset_after' => time() + $reset_after + ($reset_after * 0.1),
            );

            if (!file_put_contents($cache_file, json_encode($cache_data))) {
                return $reset_after;
            }
        }

        if ($http_code < 200 || $http_code > 401) {
            return $reset_after;
        }

        return $response;
    }

    public function checkPomelo(string $name, bool $api = false): array
    {
        $name = urldecode($name);

        if (!$api && str_starts_with($name, '-')) {
            $exceptionHandler = $this->handleExceptions($name);

            if ($exceptionHandler) {
                return $exceptionHandler;
            }
        }

        $configString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.json');
        $configJson = json_decode($configString, true);

        $url = 'https://discord.com/api/v9/users/@me/pomelo-attempt';
        $headers = [
            'accept: */*',
            'accept-language: en-US,en-GB;q=0.9,de-DE;q=0.8,ru;q=0.7',
            'Authorization: ' . $configJson['token'],
            'content-type: application/json',
            'sec-ch-ua: \'Not?A_Brand\';v=\'8\', \'Chromium\';v=\'108\'',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: \'Windows\'',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'x-debug-options: bugReporterEnabled',
            'x-discord-locale: en-US',
            'x-super-properties: eyJvcyI6IldpbmRvd3MiLCJicm93c2VyIjoiRGlzY29yZCBDbGllbnQiLCJyZWxlYXNlX2NoYW5uZWwiOiJwdGIiLCJjbGllbnRfdmVyc2lvbiI6IjEuMC4xMDI3Iiwib3NfdmVyc2lvbiI6IjEwLjAuMTkwNDQiLCJvc19hcmNoIjoieDY0Iiwic3lzdGVtX2xvY2FsZSI6ImVuLVVTIiwiY2xpZW50X2J1aWxkX251bWJlciI6MTkyOTAyLCJuYXRpdmVfYnVpbGRfbnVtYmVyIjozMTk5OCwiY2xpZW50X2V2ZW50X3NvdXJjZSI6bnVsbCwiZGVzaWduX2lkIjowfQ==' 
        ];

        $body = [
            'username' => $name,
        ];

        $resp = $this->send_request_with_rate_limit($url, $headers, $body);

        if (!$resp || is_float($resp)) {
            $attachment = '';

            if (is_float($resp)) {
                $attachment = ' (Retry after ' . round($resp) . ' seconds)';
            }

            return ['errors' => ['username' => ['_errors' => [['message' => 'This site is being ratelimited, please try again in a moment.' . $attachment, 'code' => 'NON_DISCORD_RATELIMITED']]]]];
        }

        $json = json_decode($resp, true);

        if (isset($json['errors'])) {
            if ($json['errors']['username']['_errors'][0]['code'] == 'USERNAME_INVALID_CHARACTERS') {
                return $json;
            }

            if ($json['errors']['username']['_errors'][0]['code'] == 'USERNAME_INVALID_CONTAINS') {
                preg_match('/"(.+)"/', $json['errors']['username']['_errors'][0]['message'], $matches);

                $this->savePomelo($matches[1], $json['errors']['username']['_errors'][0]['code']);
            } else {
                $this->savePomelo($name, $json['errors']['username']['_errors'][0]['code']);
            }
        } else {
            $this->deletePomelo($name);
        }

        return $json;
    }

    public function handleExceptions(string $input): array | false
    {
        $args = explode(' ', $input);

        switch ($args[0]) {
            case '-help':
                return ['content' => '> __💻 Available subcommands__' . PHP_EOL . PHP_EOL . '> Subcommands must be prefixed with "-".' . PHP_EOL . '> Arguments in () are required, arguments in [] are optional.' . PHP_EOL . PHP_EOL . '> - help : Sends this' . PHP_EOL . '> - list [code] : Lists all usernames of code [code] or lists max. 10 usernames of each code in the database if no arguments are given' . PHP_EOL . '> - query [name, code] : queries the database for specific usernames / codes. Use "%" as wildcards' . PHP_EOL . '> - api : sends all current api endpoints'];
            
            case '-list':
                $code = '';

                if (isset($args[1])) {
                    $code = $args[1];
                }

                $data = $this->list($code);

                if (empty($data)) {
                    return ['content' => 'No pomelos found.'];
                }

                if (count($data) > 10) {
                    return ['content' => 'Too many pomelos found. Please specify a code.'];
                }

                if (empty($code)) {
                    foreach ($data as $code => $pomeloList) {
                        $content .= '> Code: ' . $code . ' (' . count($pomeloList) . ')' . PHP_EOL . '> ';

                        foreach ($pomeloList as $pomelo) {
                            $content .= $pomelo . ', ' ;
                        }

                        $content = trim($content, ', ');
                        $content .= PHP_EOL . PHP_EOL;
                    }
                } else {
                    $content = '> Total invalid/taken for code "' . $code . '": ' . count($data) . PHP_EOL . PHP_EOL . '> ';

                    foreach ($data as $pomelo) {
                        $content .= $pomelo . ', ';
                    }

                    $content = trim($content, ', ');
                }

                return ['content' => $content];

            case '-query':
                $name = $args[1] ?? '%%';
                $code = $args[2] ?? '%%';

                $data = $this->query($name, $code);

                if (empty($data)) {
                    return ['content' => '> No usernames found'];
                }

                $codeCount = count($data);
                $pomeloCount = count($data, COUNT_RECURSIVE) - $codeCount;
                $content = '> Total codes found: ' . $codeCount . ' | Total usernames found: ' . $pomeloCount . PHP_EOL . PHP_EOL;

                foreach ($data as $code => $pomeloList) {
                    $content .= '> Code: ' . $code . ' (' . count($pomeloList) . ')' . PHP_EOL . '> ';

                    foreach ($pomeloList as $pomelo) {
                        $content .= $pomelo . ', ' ;
                    }

                    $content = trim($content, ', ');
                    $content .= PHP_EOL . PHP_EOL;
                }

                return ['content' => $content];
                
            case '-api':
                return ['content' => '> :globe_with_meridians: __API__' . PHP_EOL . '> Base url: ' . $_SERVER['HTTP_HOST'] . PHP_EOL . '> Api route: /api/{version}/' . PHP_EOL . '> Current api version: "v1"' . PHP_EOL . '> Example full url: https://pomelo.surgedevs.com/api/v1/request/Surge ' . PHP_EOL . '> ' . PHP_EOL . '> - ``/api/v1/request/{name}`` - gets the raw request data of name' . PHP_EOL . '> - ``/api/v1/text/{name}`` - gets processed request data of name (Your username is invalid/taken / your username is available)' . PHP_EOL . '> - ``/api/v1/query`` GET parameters: name, code - queries the database for a specific name/code and returns all matches, you can also use wildcards' . PHP_EOL . '> - ``/api/v1/list`` - gets all cached data' . PHP_EOL . '> - ``/api/v1/list/{code}`` - get all cached data with discord error code'];
        }

        return false;
    }

    public function savePomelo(string $name, string $code): void
    {
        if (strlen($name) > 32 ||strlen($name) < 2) {
            return;
        }

        $stmt = $this->connection->prepare('
            REPLACE INTO pomelo (name, code)
                VALUES (:name, :code);
        ');

        $stmt->execute([
            'name' => $name,
            'code' => $code
        ]);
    }

    public function deletePomelo(string $name): void
    {
        $stmt = $this->connection->prepare('DELETE FROM pomelo WHERE name = :name;');
        $stmt->execute(['name' => $name]);
    }

    public function list(string $code = ''): array
    {
        $data = [];

        if (empty($code)) {
            $stmt = $this->connection->prepare('SELECT name, code FROM pomelo;');
            $stmt->execute();
            $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($list as $entry) {
                if (!isset($data[$entry['code']])) {
                    $data[$entry['code']] = [];
                }

                $data[$entry['code']][] = $entry['name'];
            }
        } else {
            $stmt = $this->connection->prepare('SELECT name, code FROM pomelo WHERE code = :code;');
            $stmt->execute(['code' => $code]);
            $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($list as $entry) {
                $data[] = $entry['name'];
            }
        }

        return $data;
    }

    public function query(string $name = '%%', string $code = '%%'): array
    {
        $stmt = $this->connection->prepare('SELECT name, code FROM pomelo WHERE name LIKE :name AND code LIKE :code;');
        $stmt->execute([
            'name' => $name,
            'code' => $code
        ]);
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $data = [];

        foreach ($list as $entry) {
            if (!isset($data[$entry['code']])) {
                $data[$entry['code']] = [];
            }

            $data[$entry['code']][] = $entry['name'];
        }

        return $data;
    }
}