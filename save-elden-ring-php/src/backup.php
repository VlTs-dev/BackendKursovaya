<?php
// src/backup.php


function backupDatabaseIfNeeded(int $intervalSeconds = 600): void
{
    $dbPath = __DIR__ . '/../elden_saves.db';
    if (!file_exists($dbPath)) {
        // Базы ещё нет — нечего копировать
        return;
    }

    $backupDir = __DIR__ . '/../backups';

    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    $markerFile = $backupDir . '/last_backup.txt';

    $now = time();
    $lastBackupTs = 0;

    if (file_exists($markerFile)) {
        $content = trim(file_get_contents($markerFile));
        if ($content !== '') {
            $lastBackupTs = (int)$content;
        }
    }

    
    if ($now - $lastBackupTs < $intervalSeconds) {
        return;
    }

    
    $timestamp = date('Ymd_His');
    $backupFile = $backupDir . '/elden_saves_' . $timestamp . '.db';

    
    if (@copy($dbPath, $backupFile)) {
        file_put_contents($markerFile, (string)$now);
    }
}
