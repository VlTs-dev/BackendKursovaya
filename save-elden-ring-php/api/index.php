<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/EldenSave.php';
require_once __DIR__ . '/../src/backup.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    $db = get_db();

    
    if (($uri === '/api' || $uri === '/api/') && $method === 'GET') {
        echo json_encode(['message' => 'Elden Ring Save API (PHP) is running']);
        exit;
    }

    
    if ($uri === '/api/saves' && $method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        try {
            $payload = buildSavePayload($body);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $stmt = $db->prepare('
            INSERT INTO saves (
              user_id, game_id, slot, version, save_type,
              grace_id, region,
              character_name, character_level, character_hp, character_fp, character_stamina,
              runes, data,
              created_at, updated_at
            )
            VALUES (
              :user_id, :game_id, :slot, :version, :save_type,
              :grace_id, :region,
              :character_name, :character_level, :character_hp, :character_fp, :character_stamina,
              :runes, :data,
              :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':user_id'           => $payload['user_id'],
            ':game_id'           => $payload['game_id'],
            ':slot'              => $payload['slot'],
            ':version'           => $payload['version'],
            ':save_type'         => $payload['save_type'],
            ':grace_id'          => $payload['grace_id'],
            ':region'            => $payload['region'],
            ':character_name'    => $payload['character_name'],
            ':character_level'   => $payload['character_level'],
            ':character_hp'      => $payload['character_hp'],
            ':character_fp'      => $payload['character_fp'],
            ':character_stamina' => $payload['character_stamina'],
            ':runes'             => $payload['runes'],
            ':data'              => $payload['data'],
            ':created_at'        => $payload['timestamp'],
            ':updated_at'        => $payload['timestamp'],
        ]);

        
        backupDatabaseIfNeeded(600); // 600 секунд = 10 минут

        echo json_encode([
            'id'      => $db->lastInsertId(),
            'version' => $payload['version'],
            'message' => 'Elden save created (PHP)'
        ]);
        exit;
    }

    
    if (preg_match('#^/api/users/(\d+)/saves$#', $uri, $m) && $method === 'GET') {
        $userId = (int)$m[1];

        $stmt = $db->prepare('
          SELECT
            id,
            slot,
            version,
            save_type,
            grace_id,
            region,
            character_level,
            character_hp,
            character_fp,
            runes,
            created_at,
            updated_at
          FROM saves
          WHERE user_id = :uid
          ORDER BY slot, version DESC
        ');
        $stmt->execute([':uid' => $userId]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    
    if (preg_match('#^/api/saves/(\d+)$#', $uri, $m) && $method === 'GET') {
        $id = (int)$m[1];

        $stmt = $db->prepare('SELECT * FROM saves WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $save = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$save) {
            http_response_code(404);
            echo json_encode(['error' => 'Save not found']);
            exit;
        }

        $save['data'] = json_decode($save['data'], true);
        echo json_encode($save);
        exit;
    }

    
    if (preg_match('#^/api/saves/(\d+)$#', $uri, $m) && $method === 'PUT') {
        $id = (int)$m[1];

        $stmt = $db->prepare('SELECT * FROM saves WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Save not found']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        try {
            $payload = buildSavePayload($body, $existing);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $stmt = $db->prepare('
          UPDATE saves SET
            slot               = :slot,
            save_type          = :save_type,
            grace_id           = :grace_id,
            region             = :region,
            character_name     = :character_name,
            character_level    = :character_level,
            character_hp       = :character_hp,
            character_fp       = :character_fp,
            character_stamina  = :character_stamina,
            runes              = :runes,
            data               = :data,
            updated_at         = :updated_at
          WHERE id = :id
        ');

        $stmt->execute([
            ':slot'              => $payload['slot'],
            ':save_type'         => $payload['save_type'],
            ':grace_id'          => $payload['grace_id'],
            ':region'            => $payload['region'],
            ':character_name'    => $payload['character_name'],
            ':character_level'   => $payload['character_level'],
            ':character_hp'      => $payload['character_hp'],
            ':character_fp'      => $payload['character_fp'],
            ':character_stamina' => $payload['character_stamina'],
            ':runes'             => $payload['runes'],
            ':data'              => $payload['data'],
            ':updated_at'        => $payload['timestamp'],
            ':id'                => $id,
        ]);

        echo json_encode(['message' => 'Elden save updated (PHP)']);
        exit;
    }

    // DELETE /api/saves/{id}
    if (preg_match('#^/api/saves/(\d+)$#', $uri, $m) && $method === 'DELETE') {
        $id = (int)$m[1];

        $stmt = $db->prepare('DELETE FROM saves WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Save not found']);
            exit;
        }

        echo json_encode(['message' => 'Elden save deleted (PHP)']);
        exit;
    }

    // если ничего не совпало — 404
    http_response_code(404);
    echo json_encode(['error' => 'Not found', 'uri' => $uri, 'method' => $method]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
