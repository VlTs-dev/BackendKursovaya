<?php
// src/EldenSave.php
require_once __DIR__ . '/db.php';

function getDefaultUserAndGame(PDO $db): array {
    $userId = $db->query('SELECT id FROM users LIMIT 1')->fetchColumn();
    $gameId = $db->query('SELECT id FROM games LIMIT 1')->fetchColumn();
    return ['user_id' => $userId, 'game_id' => $gameId];
}


function buildSavePayload(array $body, ?array $existing = null): array {
    if (!isset($body['slot'])) {
        throw new Exception('slot is required');
    }
    if (!isset($body['character'])) {
        throw new Exception('character is required');
    }
    if (!isset($body['location'])) {
        throw new Exception('location is required');
    }

    $slot      = $body['slot'];
    $save_type = $body['save_type'] ?? 'grace';
    $character = $body['character'];
    $location  = $body['location'];
    $flags     = $body['flags'] ?? [];
    $inventory = $body['inventory'] ?? [];

    $name    = $character['name']    ?? null;
    $level   = $character['level']   ?? null;
    $hp      = $character['hp']      ?? null;
    $fp      = $character['fp']      ?? null;
    $stamina = $character['stamina'] ?? null;
    $runes   = $character['runes']   ?? null;

    $grace_id = $location['grace_id'] ?? null;
    $region   = $location['region']   ?? null;

    if ($name === null || $level === null || $hp === null || $fp === null || $stamina === null || $runes === null) {
        throw new Exception('character fields are incomplete');
    }
    if ($grace_id === null || $region === null) {
        throw new Exception('location fields are incomplete');
    }

    $db = get_db();

    $uid = $body['user_id'] ?? ($existing['user_id'] ?? null);
    $gid = $body['game_id'] ?? ($existing['game_id'] ?? null);

    if ($uid === null || $gid === null) {
        $defaults = getDefaultUserAndGame($db);
        if ($uid === null) $uid = $defaults['user_id'];
        if ($gid === null) $gid = $defaults['game_id'];
    }

    
    if ($existing !== null && isset($existing['version'])) {
        
        $version = (int)$existing['version'];
    } else {
        
        $stmt = $db->prepare('
            SELECT MAX(version) 
            FROM saves 
            WHERE user_id = :uid AND game_id = :gid AND slot = :slot
        ');
        $stmt->execute([
            ':uid'  => $uid,
            ':gid'  => $gid,
            ':slot' => $slot
        ]);
        $maxVersion = (int)$stmt->fetchColumn();
        $version = ($maxVersion > 0) ? $maxVersion + 1 : 1;
    }

    $now = date('Y-m-d H:i:s');

    $fullData = [
        'character' => [
            'name'    => $name,
            'level'   => $level,
            'hp'      => $hp,
            'fp'      => $fp,
            'stamina' => $stamina,
            'runes'   => $runes,
        ],
        'location' => [
            'grace_id' => $grace_id,
            'region'   => $region,
        ],
        'flags'     => $flags,
        'inventory' => $inventory,
    ];

    return [
        'user_id'            => $uid,
        'game_id'            => $gid,
        'slot'               => $slot,
        'version'            => $version,
        'save_type'          => $save_type,
        'grace_id'           => $grace_id,
        'region'             => $region,
        'character_name'     => $name,
        'character_level'    => $level,
        'character_hp'       => $hp,
        'character_fp'       => $fp,
        'character_stamina'  => $stamina,
        'runes'              => $runes,
        'data'               => json_encode($fullData, JSON_UNESCAPED_UNICODE),
        'timestamp'          => $now,
    ];
}
