<?php
// src/db.php


date_default_timezone_set('Europe/Moscow');

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = __DIR__ . '/../elden_saves.db';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('PRAGMA foreign_keys = ON;');
        init_schema($pdo);
    }

    return $pdo;
}

function init_schema(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS saves (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,

            slot INTEGER NOT NULL,
            version INTEGER NOT NULL,
            save_type TEXT NOT NULL,

            grace_id TEXT NOT NULL,
            region TEXT NOT NULL,

            character_name TEXT NOT NULL,
            character_level INTEGER NOT NULL,
            character_hp INTEGER NOT NULL,
            character_fp INTEGER NOT NULL,
            character_stamina INTEGER NOT NULL,
            runes INTEGER NOT NULL,

            data TEXT NOT NULL,

            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,

            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (game_id) REFERENCES games(id)
        );
    ");

    
    $userCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount === 0) {
        $stmt = $db->prepare("INSERT INTO users (username) VALUES (:u)");
        $stmt->execute([':u' => 'Tarnished']);
    }

    $gameCount = (int)$db->query("SELECT COUNT(*) FROM games")->fetchColumn();
    if ($gameCount === 0) {
        $stmt = $db->prepare("INSERT INTO games (name) VALUES (:n)");
        $stmt->execute([':n' => 'ELDEN RING']);
    }
}
