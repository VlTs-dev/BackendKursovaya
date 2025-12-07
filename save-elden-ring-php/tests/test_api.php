<?php
// tests/test_api.php


$baseUrl = 'http://localhost:8000';

function request(string $method, string $path, ?array $data = null): array
{
    global $baseUrl;

    $url = $baseUrl . $path;

    $headers = "Content-Type: application/json\r\n";

    $options = [
        'http' => [
            'method'        => $method,
            'header'        => $headers,
            'ignore_errors' => true, 
        ]
    ];

    if ($data !== null) {
        $options['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create($options);

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

function assertTrue(bool $cond, string $message)
{
    if ($cond) {
        echo "[OK]  $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

try {
    echo "=== Тест 1: health-check ===\n";
    $res = request('GET', '/api');
    assertTrue($res['code'] === 200, 'Код ответа = 200');
    assertTrue(isset($res['json']['message']), 'Есть поле message в ответе');

    echo "\n=== Тест 2: создание сейва ===\n";
    $payload = [
        'slot'      => 1,
        'save_type' => 'grace',
        'character' => [
            'name'    => 'Tarnished',
            'level'   => 40,
            'hp'      => 1000,
            'fp'      => 600,
            'stamina' => 150,
            'runes'   => 35000,
        ],
        'location' => [
            'grace_id' => 'Gatefront_Ruins',
            'region'   => 'Limgrave',
        ],
        'flags'     => [],
        'inventory' => [],
    ];

    $res = request('POST', '/api/saves', $payload);
    assertTrue($res['code'] === 200, 'Создание сейва: код ответа = 200');
    assertTrue(isset($res['json']['id']), 'В ответе есть id');
    assertTrue(isset($res['json']['version']), 'В ответе есть version');

    $createdId = $res['json']['id'];

    echo "\n=== Тест 3: список сейвов пользователя ===\n";
    $res = request('GET', '/api/users/1/saves');
    assertTrue($res['code'] === 200, 'Список сейвов: код ответа = 200');
    assertTrue(is_array($res['json']) && count($res['json']) > 0, 'Список сейвов не пустой');

    echo "\n=== Тест 4: получить конкретный сейв по id ===\n";
    $res = request('GET', '/api/saves/' . $createdId);
    assertTrue($res['code'] === 200, 'GET /api/saves/{id}: код 200');
    assertTrue(isset($res['json']['id']) && (int)$res['json']['id'] === (int)$createdId, 'id совпадает');
    assertTrue(isset($res['json']['data']), 'Есть поле data');

    echo "\n=== Тест 5: удалить сейв ===\n";
    $res = request('DELETE', '/api/saves/' . $createdId);
    assertTrue($res['code'] === 200, 'DELETE /api/saves/{id}: код 200');
    assertTrue(isset($res['json']['message']), 'Есть message в ответе');

    echo "\n=== Тест 6: проверка, что сейв удалён ===\n";
    $res = request('GET', '/api/saves/' . $createdId);
    assertTrue($res['code'] === 404, 'После удаления GET /api/saves/{id} возвращает 404');

    echo "\nВсе тесты выполнены.\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
