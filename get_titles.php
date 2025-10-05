<?php
/**
 * Helper ::::: YouTube Shorts Uploader
 * Automatic | YT | Shorts | Uploader
 * Multi Channel Supported
 *
 * :::::-
 *
 * @author BotolMehedi
 * @email hello@mehedi.fun
 * @version 1.0.1
 */

header('Content-Type: application/json');

$config = require 'config.php';
$channelId = $_GET['channel'] ?? '';

if (!$channelId || !isset($config['channels'][$channelId])) {
    echo json_encode(['type'=>'random','titles'=>[]]);
    exit;
}


$file = $config['channels'][$channelId]['titles_file'];

// Create Folders
$titleDir = dirname($file);
if (!file_exists($titleDir)) {
    mkdir($titleDir, 0755, true);
}


if (!file_exists($file)) {
    echo json_encode(['type'=>'random','titles'=>[]]);
    exit;
}
echo file_get_contents($file); 


?>