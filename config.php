<?php
/**
 * Config ::::: YouTube Shorts Uploader
 * Automatic | YT | Shorts | Uploader
 * Multi Channel Supported
 *
 * :::::-
 *
 * @author BotolMehedi
 * @email hello@mehedi.fun
 * @version 1.0.1
 */


$channels = [];
$jsonFile = __DIR__ . '/channels.json';

if (file_exists($jsonFile)) {
    $channels = json_decode(file_get_contents($jsonFile), true);
    if ($channels === null) {
        die('Error: Failed to decode channels.json. Check JSON syntax.');
    }
} else {
    die('Error: channels.json not found.');
}

return [
    // Config ::::: YT DATA API V3
    'google' => [
        'client_id' => '-------+++--------',
        'client_secret' => '-------+++--------',
        'redirect_uri' => '-------+++--------',
        'api_key' => '-------+++--------',
    ],
    
    // Global
    'global' => [
        'timezone' => 'Asia/Dhaka',
        'logs_directory' => 'logs/',
        'tokens_directory' => 'tokens/',
        'videos_base_directory' => 'videos/',
    ],
    
    // Channels
    'channels' => $channels,
];
