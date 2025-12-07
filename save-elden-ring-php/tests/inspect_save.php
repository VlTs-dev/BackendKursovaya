<?php
// tests/inspect_save.php


$baseUrl = 'http://localhost:8000';


function getRequest(string $path): array
{
    global $baseUrl;

    $url = $baseUrl . $path;

    $context = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'ignore_errors' => true,
        ]
    ]);

    $body = @file_get_contents($url, false, $context);

    $code = 0;
    if (isset($http_response_header[0]) &&
        preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    }

    return [
        'code' => $code,
        'body' => $body,
        'json' => json_decode($body, true),
    ];
}

echo "Интерактивный просмотр сейвов Elden Ring\n";
echo "Сервер: $baseUrl\n";
echo "Команды:\n";
echo "  - введите числовой id сейва, чтобы посмотреть информацию\n";
echo "  - exit или q — чтобы выйти\n\n";

while (true) {
    $input = readline("Введите id сейва (или 'exit'): ");

    $input = trim($input);

    if ($input === '' ) {
        continue;
    }

    if (in_array(strtolower($input), ['exit', 'q', 'quit'], true)) {
        echo "Выход.\n";
        break;
    }

    if (!ctype_digit($input)) {
        echo "Нужно ввести целое число (id).\n\n";
        continue;
    }

    $id = (int)$input;

    $res = getRequest('/api/saves/' . $id);

    echo "HTTP код: {$res['code']}\n";

    if ($res['code'] === 200 && is_array($res['json'])) {
        echo "Данные сейва:\n";
        echo json_encode($res['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } elseif ($res['code'] === 404) {
        echo "Сейв с id={$id} не найден.\n\n";
    } else {
        echo "Неожиданный ответ сервера:\n";
        echo $res['body'] . "\n\n";
    }
}
