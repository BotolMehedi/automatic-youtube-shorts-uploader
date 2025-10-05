<?php
/**
 * Publisher ::::: YouTube Shorts Uploader
 * Automatic | YT | Shorts | Uploader
 * Multi Channel Supported
 *
 * :::::-
 *
 * @author BotolMehedi
 * @email hello@mehedi.fun
 * @version 1.0.1
 */

require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\YouTube;

class AutomaticYTShortsUploader {
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->config = require 'config.php';
        $this->logFile = $this->config['global']['logs_directory'] . 'yt_shorts_uploader.log';

        $this->createDirectories();
    }
    
    private function createDirectories() {
        $directories = [
            $this->config['global']['logs_directory'],
            $this->config['global']['tokens_directory'],
            'titles',
            'uploaded'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Create channel specific folders
        foreach ($this->config['channels'] as $channelId => $channel) {
            if (!file_exists($channel['video_directory'])) {
                mkdir($channel['video_directory'], 0755, true);
            }
            
            // Unnecessary
            $uploadedDir = $channel['video_directory'] . 'uploaded/';
            if (!file_exists($uploadedDir)) {
                mkdir($uploadedDir, 0755, true);
            }
        }
    }
    
    private function log($message, $channelId = null) {
        $timestamp = date('Y-m-d H:i:s');
        $channelPrefix = $channelId ? "[$channelId] " : "";
        $logMessage = "[$timestamp] $channelPrefix$message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Channel Logs
        if ($channelId) {
            $channelLogFile = $this->config['global']['logs_directory'] . $channelId . '.log';
            file_put_contents($channelLogFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        echo $logMessage;
    }
    
    private function setupGoogleClient($channelId) {
        $channel = $this->config['channels'][$channelId];
        
        $client = new Client();
        $client->setClientId($this->config['google']['client_id']);
        $client->setClientSecret($this->config['google']['client_secret']);
        $client->setRedirectUri($this->config['google']['redirect_uri']);
        $client->addScope(YouTube::YOUTUBE_UPLOAD);
        $client->addScope(YouTube::YOUTUBE_READONLY);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        

        $this->loadAndValidateToken($client, $channel, $channelId);
        
        return $client;
    }
    
    private function loadAndValidateToken($client, $channel, $channelId) {
        if (!file_exists($channel['token_file'])) {
            throw new Exception("Access token file not found for channel '$channelId'. Please authorize this channel first.");
        }
        
        $tokenData = json_decode(file_get_contents($channel['token_file']), true);
        if (!$tokenData) {
            throw new Exception("Invalid token file for channel '$channelId'. Please re-authenticate.");
        }
        
        $client->setAccessToken($tokenData);
        
        // Token Check
        if ($this->isTokenExpiredOrExpiring($client)) {
            $this->log('Access token expired or expiring soon. Attempting to refresh...', $channelId);
            $this->refreshAccessToken($client, $channel, $channelId);
        } else {
            $this->log('Access token is valid.', $channelId);
        }
    }
    
    private function isTokenExpiredOrExpiring($client) {
        if ($client->isAccessTokenExpired()) {
            return true;
        }
        
        $token = $client->getAccessToken();
        if (isset($token['created']) && isset($token['expires_in'])) {
            $expiresAt = $token['created'] + $token['expires_in'];
            $fiveMinutesFromNow = time() + 300;
            
            if ($expiresAt <= $fiveMinutesFromNow) {
                return true;
            }
        }
        
        return false;
    }
    
    private function refreshAccessToken($client, $channel, $channelId) {
        $refreshToken = $client->getRefreshToken();
        
        if (!$refreshToken) {
            $tokenData = json_decode(file_get_contents($channel['token_file']), true);
            if (isset($tokenData['refresh_token'])) {
                $refreshToken = $tokenData['refresh_token'];
                $client->setAccessToken($tokenData);
            }
        }
        
        if ($refreshToken) {
            try {
                $this->log("Refreshing access token using refresh token...", $channelId);
                
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                if (isset($newToken['error'])) {
                    throw new Exception('Token refresh failed: ' . $newToken['error_description']);
                }
                
                
                if (!isset($newToken['refresh_token']) && $refreshToken) {
                    $newToken['refresh_token'] = $refreshToken;
                }
                
                // Save new token
                file_put_contents($channel['token_file'], json_encode($newToken));
                $this->log('Access token refreshed successfully. New expiry: ' . date('Y-m-d H:i:s', time() + $newToken['expires_in']), $channelId);
                
                $client->setAccessToken($newToken);
                
            } catch (Exception $e) {
                $this->log('Failed to refresh access token: ' . $e->getMessage(), $channelId);
                throw new Exception("Unable to refresh access token for channel '$channelId'. Please re-authenticate. Error: " . $e->getMessage());
            }
        } else {
            throw new Exception("No refresh token available for channel '$channelId'. Please re-authenticate.");
        }
    }
    
    private function shouldUploadNow($channel, $channelId) {
        $currentTime = new DateTime('now', new DateTimeZone($this->config['global']['timezone']));
        $currentHour = (int)$currentTime->format('H');
        $currentMinute = (int)$currentTime->format('i');
        
        foreach ($channel['upload_schedule'] as $timeRange) {
            if ($currentHour == $timeRange['hour']) {
                if ($currentMinute >= $timeRange['minute_start'] && $currentMinute <= $timeRange['minute_end']) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function checkLastUpload($channelId, $minHours) {
        $lastUploadFile = $this->config['global']['logs_directory'] . "last_upload_$channelId.txt";
        if (!file_exists($lastUploadFile)) {
            return true;
        }
        
        $lastUpload = file_get_contents($lastUploadFile);
        $lastUploadTime = new DateTime($lastUpload, new DateTimeZone($this->config['global']['timezone']));
        $currentTime = new DateTime('now', new DateTimeZone($this->config['global']['timezone']));
        
        $diff = $currentTime->diff($lastUploadTime);
        $hoursDiff = ($diff->days * 24) + $diff->h;
        
        return $hoursDiff >= $minHours;
    }
    
    private function updateLastUpload($channelId) {
        $currentTime = new DateTime('now', new DateTimeZone($this->config['global']['timezone']));
        $lastUploadFile = $this->config['global']['logs_directory'] . "last_upload_$channelId.txt";
        file_put_contents($lastUploadFile, $currentTime->format('Y-m-d H:i:s'));
    }
    
    private function getVideoTitle($channel, $videoPath) {
    if (!file_exists($channel['titles_file'])) {
        throw new Exception('Titles file not found: ' . $channel['titles_file']);
    }

    $titlesData = json_decode(file_get_contents($channel['titles_file']), true);
    if (!$titlesData || !isset($titlesData['type']) || !isset($titlesData['titles'])) {
        throw new Exception('Invalid titles file structure: ' . $channel['titles_file']);
    }

    $type = $titlesData['type'];
    $videoFileName = basename($videoPath);

    if ($type === 'random') {
        if (empty($titlesData['titles'])) {
            throw new Exception('No titles available in random titles list');
        }
        return $titlesData['titles'][array_rand($titlesData['titles'])];
    } elseif ($type === 'fixed') {
        foreach ($titlesData['titles'] as $item) {
            if (isset($item['name']) && $item['name'] === $videoFileName) {
                return $item['title'];
            }
        }
        throw new Exception("No matching fixed title found for video: $videoFileName");
    } else {
        throw new Exception('Unknown titles type: ' . $type);
    }
}

private function removeUploadedVideo($videoPath, $channel) {
    if (file_exists($videoPath)) {
        if (unlink($videoPath)) {
            $this->log("Deleted uploaded video: " . basename($videoPath));
            return true;
        } else {
            $this->log("Failed to delete uploaded video: " . basename($videoPath));
        }
    }
    return false;
}

    
    private function getNextVideo($channel) {
    $videoDir = $channel['video_directory'];
    if (!is_dir($videoDir)) {
        throw new Exception("Video directory not found: $videoDir");
    }
    
    $videos = glob($videoDir . '*.{mp4,mov,avi,mkv,flv,wmv,MP4,MOV,AVI}', GLOB_BRACE);
    $videos = array_filter($videos, function($video) {
        return !is_dir($video);
    });
    
    if (empty($videos)) {
        throw new Exception('No videos found in the directory: ' . $videoDir);
    }
    
    // Select Random file
    $randomIndex = array_rand($videos);
    return $videos[$randomIndex];
}

    
    
    
    public function uploadVideoToChannel($channelId) {
        if (!isset($this->config['channels'][$channelId])) {
            throw new Exception("Channel '$channelId' not found in configuration.");
        }
        
        $channel = $this->config['channels'][$channelId];
        
        if (!$channel['enabled']) {
            $this->log("Channel is disabled. Skipping.", $channelId);
            return false;
        }
        
        // Check if it's time to upload
        if (!$this->shouldUploadNow($channel, $channelId)) {
            $this->log("Not in upload time period. Skipping.", $channelId);
            return false;
        }
        
        // If you want you can use it
        /*
        if (!$this->checkLastUpload($channelId, $channel['min_hours_between_uploads'])) {
            $this->log("Not enough time passed since last upload. Skipping.", $channelId);
            return false;
        }
        */
        
        try {
            
            $client = $this->setupGoogleClient($channelId);
            $youtube = new YouTube($client);
            
            
            $videoPath = $this->getNextVideo($channel);
            $this->log("Selected video: " . basename($videoPath), $channelId);
            

$title = $this->getVideoTitle($channel, $videoPath);
$this->log("Selected title: $title", $channelId);

            
            // VDO Description & Tags
            $tags = implode(' #', $channel['upload_settings']['tags']);
            $description = $channel['upload_settings']['description'];

            
            // Upload
            $result = $this->uploadVideo($youtube, $videoPath, $title, $description, $channel, $channelId);
            
            if ($result) {
                $this->log("Upload successful! Video ID: " . $result->getId(), $channelId);
                $this->log("Video URL: https://youtube.com/watch?v=" . $result->getId(), $channelId);
                
                
     // Delete uploaded video
$this->removeUploadedVideo($videoPath, $channel);

                
                // Update last upload time
                $this->updateLastUpload($channelId);
                
                return $result;
            }
            
        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage(), $channelId);
            throw $e;
        }
        
        return false;
    }
    
    private function uploadVideo($youtube, $videoPath, $title, $description, $channel, $channelId) {
        try {
            $video = new Google\Service\YouTube\Video();
            $videoSnippet = new Google\Service\YouTube\VideoSnippet();
            
            $videoSnippet->setTitle($title);
            $videoSnippet->setDescription($description);
            $videoSnippet->setCategoryId($channel['upload_settings']['category_id']);
            $videoSnippet->setTags($channel['upload_settings']['tags']);
            $videoSnippet->setDefaultLanguage($channel['upload_settings']['default_language']);
            $videoSnippet->setDefaultAudioLanguage($channel['upload_settings']['default_language']);
            
            $video->setSnippet($videoSnippet);
            
            // Set video status
            $videoStatus = new Google\Service\YouTube\VideoStatus();
            $videoStatus->setPrivacyStatus($channel['upload_settings']['privacy_status']);
            $videoStatus->setMadeForKids($channel['upload_settings']['made_for_kids']);
            $video->setStatus($videoStatus);
            
            $this->log("Starting upload for: $title", $channelId);
            $this->log("File size: " . $this->formatBytes(filesize($videoPath)), $channelId);
            
            // Upload retry
            $maxRetries = 3;
            $retryCount = 0;
            
            while ($retryCount < $maxRetries) {
                try {
                    $insertRequest = $youtube->videos->insert(
                        'status,snippet',
                        $video,
                        [
                            'data' => file_get_contents($videoPath),
                            'mimeType' => 'video/*',
                            'uploadType' => 'multipart'
                        ]
                    );
                    
                    $this->log("Video uploaded successfully!", $channelId);
                    return $insertRequest;
                    
                } catch (Exception $e) {
                    $retryCount++;
                    
                    if (strpos($e->getMessage(), 'Invalid Credentials') !== false || 
                        strpos($e->getMessage(), 'unauthorized') !== false) {
                        
                        $this->log("Authentication error during upload attempt $retryCount. Refreshing token...", $channelId);
                        
                        // refresh token
                        $client = $this->setupGoogleClient($channelId);
                        $youtube = new YouTube($client);
                        
                        if ($retryCount < $maxRetries) {
                            $this->log("Retrying upload after token refresh...", $channelId);
                            sleep(2);
                            continue;
                        }
                    }
                    
                    if ($retryCount >= $maxRetries) {
                        throw new Exception("Upload failed after $maxRetries attempts. Last error: " . $e->getMessage());
                    }
                    
                    throw $e;
                }
            }
            
        } catch (Exception $e) {
            $this->log("Error uploading video: " . $e->getMessage(), $channelId);
            throw $e;
        }
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function runForChannel($channelId) {
        $this->log("=== Upload Starting | $channelId ===", $channelId);
        
        try {
            $result = $this->uploadVideoToChannel($channelId);
            if ($result) {
                $this->log("✅ Video Uploaded successfully", $channelId);
            } else {
                $this->log("ℹ️ Video uploading skipped (timing/conditions not met)", $channelId);
            }
        } catch (Exception $e) {
            $this->log("❌ Video Upload failed: " . $e->getMessage(), $channelId);
            
            if (strpos($e->getMessage(), 'refresh') !== false || 
                strpos($e->getMessage(), 'authenticate') !== false) {
                $this->log("IMPORTANT: Authentication issue detected for $channelId. Re-authorization may be needed.", $channelId);
            }
        }
        
        $this->log("=== Uploading Finished | $channelId ===", $channelId);
    }
    
    public function runAllChannels() {
        $this->log("=== Uploading Process Running ===");
        $this->log("Timezone: " . $this->config['global']['timezone']);
        $this->log("Current time: " . date('Y-m-d H:i:s'));
        
        $totalChannels = 0;
        $successfulUploads = 0;
        $skippedChannels = 0;
        $errorChannels = 0;
        
        foreach ($this->config['channels'] as $channelId => $channel) {
            $totalChannels++;
            
            if (!$channel['enabled']) {
                $this->log("Channel '$channelId' is disabled. Skipping.", $channelId);
                $skippedChannels++;
                continue;
            }
            
            try {
                $result = $this->uploadVideoToChannel($channelId);
                if ($result) {
                    $successfulUploads++;
                    $this->log("✅ Upload successful for $channelId");
                } else {
                    $skippedChannels++;
                    $this->log("ℹ️ Upload skipped for $channelId");
                }
                
                // Wait between channels to avoid API rate limits
                sleep(2);
                
            } catch (Exception $e) {
                $errorChannels++;
                $this->log("❌ Error for channel $channelId: " . $e->getMessage());
            }
        }
        
        // Summary
        $this->log("=== Upload Summary ===");
        $this->log("Total channels: $totalChannels");
        $this->log("Successful uploads: $successfulUploads");
        $this->log("Skipped channels: $skippedChannels");
        $this->log("Error channels: $errorChannels");
        $this->log("=== Uploading Process Finished ===");
    }
    
    public function getChannelStatus($channelId = null) {
        $status = [];
        $channels = $channelId ? [$channelId => $this->config['channels'][$channelId]] : $this->config['channels'];
        
        foreach ($channels as $id => $channel) {
            $tokenExists = file_exists($channel['token_file']);
            $videosCount = count(glob($channel['video_directory'] . '*.{mp4,mov,avi,mkv,flv,wmv,MP4,MOV,AVI}', GLOB_BRACE));
            $titlesCount = 0;
            
            if (file_exists($channel['titles_file'])) {
                $titles = json_decode(file_get_contents($channel['titles_file']), true);
                $titlesCount = isset($titles['titles']) && is_array($titles['titles']) 
                   ? count($titles['titles']) 
                   : 0;
            }
            
            $lastUploadFile = $this->config['global']['logs_directory'] . "last_upload_$id.txt";
            $lastUpload = file_exists($lastUploadFile) ? file_get_contents($lastUploadFile) : 'Never';
            
            $status[$id] = [
                'name' => $channel['name'],
                'enabled' => $channel['enabled'],
                'authorized' => $tokenExists,
                'videos_available' => $videosCount,
                'titles_available' => $titlesCount,
                'last_upload' => $lastUpload,
                'next_upload_windows' => $this->getNextUploadWindows($channel),
            ];
        }
        
        return $status;
    }
    
    private function getNextUploadWindows($channel) {
        $windows = [];
        $currentTime = new DateTime('now', new DateTimeZone($this->config['global']['timezone']));
        $today = $currentTime->format('Y-m-d');
        
        foreach ($channel['upload_schedule'] as $timeWindow) {
            $windowStart = new DateTime("$today {$timeWindow['hour']}:{$timeWindow['minute_start']}:00", 
                                      new DateTimeZone($this->config['global']['timezone']));
            $windowEnd = new DateTime("$today {$timeWindow['hour']}:{$timeWindow['minute_end']}:59", 
                                    new DateTimeZone($this->config['global']['timezone']));
            
            // Time Periods
            if ($windowEnd < $currentTime) {
                $windowStart->add(new DateInterval('P1D'));
                $windowEnd->add(new DateInterval('P1D'));
            }
            
            $windows[] = $windowStart->format('H:i') . '-' . $windowEnd->format('H:i');
        }
        
        return $windows;
    }
}


$channelId = $argv[1] ?? null;

try {
    $uploader = new AutomaticYTShortsUploader();
    
    if ($channelId && $channelId !== 'all') {
        
        $uploader->runForChannel($channelId);
    } else {
        
        $uploader->runAllChannels();
    }
    
} catch (Exception $e) {
    error_log("Critical Error: " . $e->getMessage());
    echo "Critical Error: " . $e->getMessage() . "\n";
}