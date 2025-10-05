<?php
/**
 * Dashboard ::::: YouTube Shorts Uploader
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
require_once 'publish.php';
$channels_list = __DIR__ . '/channels.json';

use Google\Client;
use Google\Service\YouTube;

$config = require 'config.php';
$action = $_GET['action'] ?? 'dashboard';
$channelId = $_GET['channel'] ?? null;

// AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    handleAjaxRequest();
    exit;
}

function handleAjaxRequest() {
    global $config, $channels_list;
    $action = $_POST['action'] ?? '';
    $channelId = $_POST['channel'] ?? '';
    
    switch ($action) {
        case 'toggle_channel':
            $result = toggleChannelStatus($channelId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'update_schedule':
            $schedule = json_decode($_POST['schedule'], true);
            $result = updateChannelSchedule($channelId, $schedule);
            echo json_encode(['success' => $result]);
            break;
            
        case 'update_settings':
            $settings = json_decode($_POST['settings'], true);
            $result = updateChannelSettings($channelId, $settings);
            echo json_encode(['success' => $result]);
            break;
            
        case 'save_titles':
            $titles = json_decode($_POST['titles'], true);
            $result = saveChannelTitles($channelId, $titles);
            echo json_encode(['success' => $result]);
            break;
            
        case 'test_upload':
            $result = testChannelUpload($channelId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

function toggleChannelStatus($channelId) {
    global $channels_list;
    
    if (!file_exists($channels_list)) {
        return false;
    }
    
    $channels = json_decode(file_get_contents($channels_list), true);
    
    if (!isset($channels[$channelId])) {
        return false;
    }
    
    $channels[$channelId]['enabled'] = !($channels[$channelId]['enabled'] ?? true);
    
    return file_put_contents($channels_list, json_encode($channels, JSON_PRETTY_PRINT)) !== false;
}

function updateChannelSchedule($channelId, $schedule) {
    global $channels_list;
    
    if (!file_exists($channels_list)) {
        return false;
    }
    
    $channels = json_decode(file_get_contents($channels_list), true);
    
    if (!isset($channels[$channelId])) {
        return false;
    }
    
    $channels[$channelId]['upload_schedule'] = $schedule;
    
    return file_put_contents($channels_list, json_encode($channels, JSON_PRETTY_PRINT)) !== false;
}

function updateChannelSettings($channelId, $settings) {
    global $channels_list;
    
    if (!file_exists($channels_list)) {
        return false;
    }
    
    $channels = json_decode(file_get_contents($channels_list), true);
    
    if (!isset($channels[$channelId])) {
        return false;
    }
    
    // Update Data
    if (isset($settings['name'])) {
        $channels[$channelId]['name'] = $settings['name'];
    }
    
    if (isset($settings['description'])) {
        $channels[$channelId]['description'] = $settings['description'];
    }
    
    if (isset($settings['video_directory'])) {
        $channels[$channelId]['video_directory'] = $settings['video_directory'];
    }
    
    if (isset($settings['titles_file'])) {
        $channels[$channelId]['titles_file'] = $settings['titles_file'];
    }
    
    if (isset($settings['token_file'])) {
        $channels[$channelId]['token_file'] = $settings['token_file'];
    }
    
    if (isset($settings['enabled'])) {
        $channels[$channelId]['enabled'] = $settings['enabled'];
    }
    
    if (isset($settings['upload_settings'])) {
        $channels[$channelId]['upload_settings'] = array_merge(
            $channels[$channelId]['upload_settings'] ?? [],
            $settings['upload_settings']
        );
    }
    
    if (isset($settings['upload_schedule'])) {
        $channels[$channelId]['upload_schedule'] = $settings['upload_schedule'];
    }
    
    if (isset($settings['min_hours_between_uploads'])) {
        $channels[$channelId]['min_hours_between_uploads'] = $settings['min_hours_between_uploads'];
    }
    
    return file_put_contents($channels_list, json_encode($channels, JSON_PRETTY_PRINT)) !== false;
}

function saveChannelTitles($channelId, $titlesData) {
    global $config;

    if (!isset($config['channels'][$channelId])) {
        return false;
    }

    $titlesFile = $config['channels'][$channelId]['titles_file'];
    $titleDir = dirname($titlesFile);
    if (!file_exists($titleDir)) {
        mkdir($titleDir, 0755, true);
    }

    return file_put_contents($titlesFile, json_encode($titlesData, JSON_PRETTY_PRINT)) !== false;
}


function testChannelUpload($channelId) {
    try {
        
        $uploader = new AutomaticYTShortsUploader();
        
        $result = $uploader->uploadVideoToChannel($channelId);
        
        if ($result) {
            return ['success' => true, 'message' => 'Test upload successful!', 'video_id' => $result->getId()];
        } else {
            return ['success' => false, 'message' => 'Upload conditions not met or no videos available'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}


$uploader = new AutomaticYTShortsUploader();
$channelStatuses = $uploader->getChannelStatus();


?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Automatic YouTube Shorts Uploader</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      :root { --glass: rgba(255,255,255,0.06); }
      html,body { height:100%; }
      .backdrop-blur { backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
      pre { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-gray-100 text-xs antialiased">
  <div class="max-w-7xl mx-auto p-4 md:p-6">
    <header class="flex items-center justify-between gap-4 mb-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-yellow-400 flex items-center justify-center shadow-lg">
          <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4v16l16-8L4 4z" fill="currentColor"/></svg>
        </div>
        <div>
          <h1 class="text-sm font-semibold leading-tight">AYTSU</h1>

        </div>
      </div>

      <div class="flex items-center gap-2">
        <div class="text-right mr-2">
          <div id="currentTime" class="text-xxs text-gray-300"></div>
          <div class="text-xxs text-gray-400">Auto-refresh: 5m</div>
        </div>
      </div>
    </header>

    <main class="grid grid-cols-1 lg:grid-cols-4 gap-4">
      <!-- Left: Quick controls + logs -->
      <aside class="lg:col-span-1 space-y-4">
        <div class="rounded-xl bg-[var(--glass)] p-3 shadow-md border border-white/5">
          <h3 class="font-semibold mb-2">Quick Actions</h3>
          <div class="flex flex-col gap-2">
            <button onclick="runAllChannels()" class="w-full py-2 rounded-md bg-emerald-500 hover:bg-emerald-600 text-white font-medium">▶️ Run All</button>
            <button onclick="refreshDashboard()" class="w-full py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-medium">🔄 Refresh</button>
            <a href="oauth_handler.php" class="w-full block text-center py-2 rounded-md bg-amber-400 hover:bg-amber-500 text-slate-900 font-medium">🔐 New Channel</a>
            <button onclick="showLogs()" class="w-full py-2 rounded-md bg-gray-700 hover:bg-gray-600 text-white font-medium">📋 View Logs</button>
            <button onclick="exportConfig()" class="w-full py-2 rounded-md bg-gray-700 hover:bg-gray-600 text-white font-medium">💾 Export Config</button>
          </div>
        </div>

        <div id="logViewer" class="rounded-xl bg-[var(--glass)] p-3 shadow-md border border-white/5 hidden">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">📋 System Logs</h3>
            <button onclick="hideLogs()" class="text-xxs px-2 py-1 rounded bg-red-600">Close</button>
          </div>
          <div class="flex flex-wrap gap-1 mb-2">
            <button onclick="loadLogs('yt_shorts_uploader.log')" class="px-2 py-1 rounded bg-slate-700">Main Log</button>
            <?php foreach ($config['channels'] as $id => $channel): ?>
              <button onclick="loadLogs('<?= $id ?>.log')" class="px-2 py-1 rounded bg-slate-700"><?= htmlspecialchars($channel['name']) ?></button>
            <?php endforeach; ?>
          </div>
          <div id="logContent" class="bg-black/60 rounded p-2 max-h-64 overflow-auto text-xxs font-mono text-green-300">Select a log file to view...</div>
        </div>

        <div class="rounded-xl bg-[var(--glass)] p-3 shadow-md border border-white/5">
          <h3 class="font-semibold mb-2">Status</h3>
          <p class="text-xxs text-gray-300">Channels: <span class="font-medium"><?= count($channelStatuses) ?></span></p>
          <p class="text-xxs text-gray-300 mt-1">Version: <span class="font-medium">v1.0.1</span></p>
        </div>
      </aside>

      <!-- Right: Channels grid -->
      <section class="lg:col-span-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <?php foreach ($channelStatuses as $id => $status): ?>
            <div id="channel-<?= $id ?>" class="rounded-2xl bg-gradient-to-b from-white/3 to-white/2 p-3 shadow-lg border border-white/6">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-lg bg-slate-600 flex items-center justify-center text-white font-bold"><?= strtoupper(substr($status['name'],0,2)) ?></div>
                    <div>
                      <div class="font-semibold"><?= htmlspecialchars($status['name']) ?></div>
                      <div class="text-xxs text-gray-300"><?= htmlspecialchars($status['id'] ?? $id) ?></div>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col items-end gap-1">
                  <?php if ($status['authorized']): ?>
                    <span class="px-2 py-1 rounded-full bg-emerald-500 text-white text-xxs font-semibold">✅ Authorized</span>
                  <?php else: ?>
                    <span class="px-2 py-1 rounded-full bg-red-500 text-white text-xxs font-semibold">❌ Not Authorized</span>
                  <?php endif; ?>

                  <?php if (!$status['enabled']): ?>
                    <span class="mt-1 px-2 py-1 rounded-full bg-gray-600 text-white text-xxs">💤 Disabled</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 mt-3 text-xxs">
                <div class="bg-white/5 p-2 rounded">
                  <div class="text-gray-300">📁 Videos</div>
                  <div class="font-semibold"><?= $status['videos_available'] ?></div>
                </div>
                <div class="bg-white/5 p-2 rounded">
                  <div class="text-gray-300">📝 Titles</div>
                  <div class="font-semibold"><?= $status['titles_available'] ?></div>
                </div>
                <div class="bg-white/5 p-2 rounded">
                  <div class="text-gray-300">⏰ Last Upload</div>
                  <div class="font-semibold"><?= $status['last_upload'] !== 'Never' ? date('M d, H:i', strtotime($status['last_upload'])) : 'Never' ?></div>
                </div>
                <div class="bg-white/5 p-2 rounded">
                  <div class="text-gray-300">🎯 Next Time</div>
                  <div class="font-semibold"><?= implode(', ', $status['next_upload_windows']) ?></div>
                </div>
              </div>

              <div class="flex flex-wrap gap-2 mt-3">
                <?php if ($status['authorized']): ?>
                  <button onclick="runChannel('<?= $id ?>')" class="flex-1 min-w-[90px] py-2 rounded-md bg-emerald-500 hover:bg-emerald-600 text-white">▶️ Run</button>
                  <button onclick="testChannel('<?= $id ?>')" class="flex-1 min-w-[90px] py-2 rounded-md bg-amber-400 hover:bg-amber-500 text-slate-900">🧪 Test</button>
                <?php else: ?>
                  <a href="oauth_handler.php?channel=<?= $id ?>" class="flex-1 min-w-[90px] py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white text-center">🔐 Authorize</a>
                <?php endif; ?>

                <button onclick="editChannel('<?= $id ?>')" class="py-2 px-3 rounded-md bg-slate-700 hover:bg-slate-600 text-white">⚙️ Settings</button>
                <button onclick="editTitles('<?= $id ?>')" class="py-2 px-3 rounded-md bg-slate-700 hover:bg-slate-600 text-white">📝 Titles</button>

                <?php if ($status['enabled']): ?>
                  <button onclick="toggleChannel('<?= $id ?>', false)" class="py-2 px-3 rounded-md bg-yellow-500 hover:bg-yellow-600 text-slate-900">⏸️ Disable</button>
                <?php else: ?>
                  <button onclick="toggleChannel('<?= $id ?>', true)" class="py-2 px-3 rounded-md bg-emerald-500 hover:bg-emerald-600 text-white">▶️ Enable</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>

    <!-- Modals -->
    <div id="settingsModal" class="fixed inset-0 z-50 hidden items-center justify-center">
      <div class="absolute inset-0 bg-black/60 backdrop-blur" onclick="closeModal('settingsModal')"></div>
      <div class="relative w-full max-w-4xl mx-auto p-4 max-h-[90vh] overflow-y-auto">
        <div class="rounded-2xl bg-gradient-to-b from-slate-800 to-slate-900 p-4 shadow-lg border border-white/5">
          <div class="flex items-start justify-between">
            <h2 class="font-semibold">⚙️ Channel Settings</h2>
            <button class="text-gray-300 text-2xl leading-none" onclick="closeModal('settingsModal')">&times;</button>
          </div>
          <div id="settingsContent" class="mt-3 text-xxs"></div>
        </div>
      </div>
    </div>

    <div id="titlesModal" class="fixed inset-0 z-50 hidden items-center justify-center">
      <div class="absolute inset-0 bg-black/60 backdrop-blur" onclick="closeModal('titlesModal')"></div>
      <div class="relative w-full max-w-3xl mx-auto p-4">
        <div class="rounded-2xl bg-gradient-to-b from-slate-800 to-slate-900 p-4 shadow-lg border border-white/5">
          <div class="flex items-start justify-between">
            <h2 class="font-semibold">📝 Edit Titles</h2>
            <button class="text-gray-300 text-2xl leading-none" onclick="closeModal('titlesModal')">&times;</button>
          </div>
          <div id="titlesContent" class="mt-3 text-xxs"></div>
        </div>
      </div>
    </div>

    <div id="resultsModal" class="fixed inset-0 z-50 hidden items-center justify-center">
      <div class="absolute inset-0 bg-black/60 backdrop-blur" onclick="closeModal('resultsModal')"></div>
      <div class="relative w-full max-w-2xl mx-auto p-4">
        <div class="rounded-2xl bg-gradient-to-b from-slate-800 to-slate-900 p-4 shadow-lg border border-white/5">
          <div class="flex items-start justify-between">
            <h2 id="resultsTitle" class="font-semibold">Results</h2>
            <button class="text-gray-300 text-2xl leading-none" onclick="closeModal('resultsModal')">&times;</button>
          </div>
          <div id="resultsContent" class="mt-3 text-xxs"></div>
        </div>
      </div>
    </div>

    <footer class="mt-6 text-center text-gray-400 text-xxs">
      <div>AYTSU | v1.0.1</div>
    </footer>
  </div>

  <script>
    
    function refreshDashboard() {
        location.reload();
    }

    function runAllChannels() {
        showLoading('Running all channels...');
        
        fetch('publish.php')
            .then(response => response.text())
            .then(data => {
                showResults('All Channels Run Complete', '<pre>' + escapeHtml(data) + '</pre>');
            })
            .catch(error => {
                showResults('Error', 'Failed to run channels: ' + error.message);
            });
    }

    function runChannel(channelId) {
        showLoading(`Running channel: ${channelId}...`);
        
        fetch(`publish.php?channel=${channelId}`)
            .then(response => response.text())
            .then(data => {
                showResults(`Channel ${channelId} Complete`, '<pre>' + escapeHtml(data) + '</pre>');
                setTimeout(() => refreshDashboard(), 2000);
            })
            .catch(error => {
                showResults('Error', `Failed to run channel ${channelId}: ` + error.message);
            });
    }

    function testChannel(channelId) {
        showLoading(`Testing channel: ${channelId}...`);
        
        const formData = new FormData();
        formData.append('action', 'test_upload');
        formData.append('channel', channelId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResults('Test Successful', `Channel ${channelId} test upload completed! Video ID: ${data.video_id || 'N/A'}`);
            } else {
                showResults('Test Failed', data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            showResults('Test Error', 'Failed to test channel: ' + error.message);
        });
    }

    function toggleChannel(channelId, enable) {
        const action = enable ? 'enable' : 'disable';
        showLoading(`${action.charAt(0).toUpperCase() + action.slice(1)}ing channel: ${channelId}...`);
        
        const formData = new FormData();
        formData.append('action', 'toggle_channel');
        formData.append('channel', channelId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => refreshDashboard(), 1000);
            } else {
                showResults('Error', `Failed to ${action} channel`);
            }
        })
        .catch(error => {
            showResults('Error', `Failed to ${action} channel: ` + error.message);
        });
    }

    function editChannel(channelId) {
        const config = <?= json_encode($config['channels']) ?>;
        const channel = config[channelId];
        
        document.getElementById('settingsContent').innerHTML = `
            <div class="space-y-4">
                <!-- Basic Info Section -->
                <div class="bg-white/5 p-3 rounded-lg">
                    <h3 class="font-semibold mb-3 text-sm">📌 Basic Information</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Channel Name:</label>
                            <input id="channelName" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.name)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Description:</label>
                            <input id="channelDesc" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.description)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Enabled:</label>
                            <input id="channelEnabled" type="checkbox" class="w-5 h-5" ${channel.enabled ? 'checked' : ''}>
                        </div>
                    </div>
                </div>

                <!-- File Paths Section -->
                <div class="bg-white/5 p-3 rounded-lg">
                    <h3 class="font-semibold mb-3 text-sm">📂 File Paths</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Video Directory:</label>
                            <input id="videoDir" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.video_directory)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Titles File:</label>
                            <input id="titlesFile" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.titles_file)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Token File:</label>
                            <input id="tokenFile" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.token_file)}">
                        </div>
                    </div>
                </div>

                <!-- Upload Settings Section -->
                <div class="bg-white/5 p-3 rounded-lg">
                    <h3 class="font-semibold mb-3 text-sm">🎬 Upload Settings</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Category ID:</label>
                            <input id="categoryId" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.upload_settings.category_id)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Privacy Status:</label>
                            <select id="privacyStatus" class="flex-1 p-2 rounded bg-slate-700 text-xs">
                                <option value="public" ${channel.upload_settings.privacy_status === 'public' ? 'selected' : ''}>Public</option>
                                <option value="unlisted" ${channel.upload_settings.privacy_status === 'unlisted' ? 'selected' : ''}>Unlisted</option>
                                <option value="private" ${channel.upload_settings.privacy_status === 'private' ? 'selected' : ''}>Private</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Made for Kids:</label>
                            <input id="madeForKids" type="checkbox" class="w-5 h-5" ${channel.upload_settings.made_for_kids ? 'checked' : ''}>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Description:</label>
                            <textarea id="uploadDesc" class="flex-1 p-2 rounded bg-slate-700 text-xs" rows="2">${escapeHtml(channel.upload_settings.description.join('\n'))}</textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Tags:</label>
                            <input id="uploadTags" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.upload_settings.tags.join(', '))}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Default Language:</label>
                            <input id="defaultLang" class="flex-1 p-2 rounded bg-slate-700 text-xs" type="text" value="${escapeHtml(channel.upload_settings.default_language)}">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="w-32 text-xxs">Min Hours Between:</label>
                            <input id="minHours" class="w-28 p-2 rounded bg-slate-700 text-xs" type="number" min="1" max="24" value="${channel.min_hours_between_uploads}">
                        </div>
                    </div>
                </div>

                <!-- Upload Schedule Section -->
                <div class="bg-white/5 p-3 rounded-lg">
                    <h3 class="font-semibold mb-3 text-sm">⏰ Upload Schedule</h3>
                    <div id="scheduleEditor" class="space-y-2">
                        ${channel.upload_schedule.map((schedule, index) => `
                            <div class="flex items-center justify-between bg-slate-800 p-2 rounded">
                                <div class="text-xs font-medium">Slot ${index + 1}</div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xxs">Hour:</label>
                                    <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="23" value="${schedule.hour}" id="hour_${index}">
                                    <label class="text-xxs">Min Start:</label>
                                    <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="59" value="${schedule.minute_start}" id="start_${index}">
                                    <label class="text-xxs">Min End:</label>
                                    <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="59" value="${schedule.minute_end}" id="end_${index}">
                                    <button onclick="removeSchedule(${index})" class="px-2 py-1 rounded bg-red-600 text-white text-xxs hover:bg-red-700">❌</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="mt-3">
                        <button onclick="addSchedule()" class="px-4 py-2 rounded bg-emerald-500 text-white text-xs hover:bg-emerald-600">➕ Add Schedule</button>
                    </div>
                </div>

                <!-- Save Buttons -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-white/10">
                    <button onclick="closeModal('settingsModal')" class="px-4 py-2 rounded bg-gray-600 text-white text-xs hover:bg-gray-700">Cancel</button>
                    <button onclick="saveChannelSettings('${channelId}')" class="px-4 py-2 rounded bg-emerald-500 text-white text-xs hover:bg-emerald-600">💾 Save All Settings</button>
                </div>
            </div>
        `;
        
        document.getElementById('settingsModal').style.display = 'flex';
        document.getElementById('settingsModal').classList.remove('hidden');
    }

    function editTitles(channelId) {
    fetch(`get_titles.php?channel=${channelId}`)
        .then(response => response.json())
        .then(data => {
            const type = data.type || "random";
            const titles = data.titles || [];

            let textareaContent = "";
            if (type === "random") {
                textareaContent = titles.join("\n");
            } else {
                textareaContent = titles.map(t => `${t.name} | ${t.title}`).join("\n");
            }

            document.getElementById('titlesContent').innerHTML = `
                <h3 class="font-semibold">Edit Titles for Channel: ${escapeHtml(channelId)}</h3>
                
                <div class="mt-2 flex gap-4 text-xxs">
                  <label><input type="radio" name="titleType" value="random" ${type==="random"?"checked":""}> Random</label>
                  <label><input type="radio" name="titleType" value="fixed" ${type==="fixed"?"checked":""}> Fixed</label>
                </div>

                <p class="text-xxs mt-2">Enter titles below:</p>
                <textarea id="titlesTextarea" class="w-full mt-2 p-2 rounded bg-black/70 text-xxs h-40"
                placeholder="Random: one per line\nFixed: filename.mp4 | title">${escapeHtml(textareaContent)}</textarea>

                <div class="mt-3 flex justify-end gap-2">
                    <button onclick="closeModal('titlesModal')" class="px-3 py-2 rounded bg-gray-600 text-white">Cancel</button>
                    <button onclick="saveTitles('${channelId}')" class="px-3 py-2 rounded bg-emerald-500 text-white">💾 Save Titles</button>
                </div>
            `;

            document.getElementById('titlesModal').style.display = 'flex';
            document.getElementById('titlesModal').classList.remove('hidden');
        })
        .catch(error => {
            showResults('Error', 'Failed to load titles: ' + error.message);
        });
}

function saveTitles(channelId) {
    const type = document.querySelector('input[name="titleType"]:checked').value;
    const lines = document.getElementById('titlesTextarea').value
        .split('\n').map(l => l.trim()).filter(l => l !== '');

    let titles = [];
    if (type === "random") {
        titles = lines;
    } else {
        titles = lines.map(l => {
            const parts = l.split('|');
            return { name: (parts[0]||"").trim(), title: (parts[1]||"").trim() };
        });
    }

    const formData = new FormData();
    formData.append('action', 'save_titles');
    formData.append('channel', channelId);
    formData.append('titles', JSON.stringify({ type, titles }));

    fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('titlesModal');
                showResults('Success', `Saved ${titles.length} ${type} titles for channel ${channelId}`);
                setTimeout(() => refreshDashboard(), 1000);
            } else {
                showResults('Error', 'Failed to save titles');
            }
        })
        .catch(error => {
            showResults('Error', 'Failed to save titles: ' + error.message);
        });
}


    function showLogs() {
        const lv = document.getElementById('logViewer');
        lv.classList.toggle('hidden');
        if (!lv.classList.contains('hidden')) {
          lv.style.display = 'block';
        } else {
          lv.style.display = 'none';
        }
    }

    function hideLogs() {
        const lv = document.getElementById('logViewer');
        lv.classList.add('hidden');
        lv.style.display = 'none';
    }

    function loadLogs(logFile) {
        fetch(`logs/${logFile}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('logContent').innerHTML = `<pre>${escapeHtml(data)}</pre>`;
            })
            .catch(error => {
                document.getElementById('logContent').innerHTML = `Error loading log: ${escapeHtml(error.message)}`;
            });
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }

    function showLoading(message) {
        showResults('Please Wait...', message);
    }

    function showResults(title, content) {
        document.getElementById('resultsTitle').textContent = title;
        document.getElementById('resultsContent').innerHTML = content;
        const modal = document.getElementById('resultsModal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }

    function exportConfig() {
        const config = <?= json_encode($config, JSON_PRETTY_PRINT) ?>;
        const blob = new Blob([JSON.stringify(config, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'youtube_uploader_config.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    window.onclick = function(event) {
        const modals = ['settingsModal', 'titlesModal', 'resultsModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        });
    }

    setInterval(() => {
        refreshDashboard();
    }, 300000);

    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleString('en-US', {
            timeZone: 'Asia/Dhaka',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const timeDisplay = document.getElementById('currentTime');
        if (timeDisplay) {
            timeDisplay.textContent = timeString;
        }
    }

    setInterval(updateTime, 1000);
    updateTime();

    function escapeHtml(unsafe) {
      if (unsafe === null || unsafe === undefined) return '';
      return String(unsafe)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function addSchedule() {
      const editor = document.getElementById('scheduleEditor');
      const idx = editor.children.length;
      const wrapper = document.createElement('div');
      wrapper.className = 'flex items-center justify-between bg-slate-800 p-2 rounded';
      wrapper.innerHTML = `
        <div class="text-xs font-medium">Slot ${idx+1}</div>
        <div class="flex items-center gap-2">
          <label class="text-xxs">Hour:</label>
          <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="23" id="hour_${idx}" value="0">
          <label class="text-xxs">Min Start:</label>
          <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="59" id="start_${idx}" value="0">
          <label class="text-xxs">Min End:</label>
          <input class="w-16 p-1 rounded bg-slate-700 text-xs" type="number" min="0" max="59" id="end_${idx}" value="30">
          <button onclick="this.closest('.flex').remove()" class="px-2 py-1 rounded bg-red-600 text-white text-xxs hover:bg-red-700">❌</button>
        </div>
      `;
      editor.appendChild(wrapper);
    }

    function removeSchedule(index) {
      const editor = document.getElementById('scheduleEditor');
      const item = document.getElementById(`hour_${index}`);
      if (item) {
        let wrapper = item.closest('.flex.items-center');
        if (wrapper) wrapper.remove();
      }
    }

    function saveChannelSettings(channelId) {
      // Collect all settings from the form
      const name = document.getElementById('channelName').value.trim();
      const description = document.getElementById('channelDesc').value.trim();
      const enabled = document.getElementById('channelEnabled').checked;
      const videoDir = document.getElementById('videoDir').value.trim();
      const titlesFile = document.getElementById('titlesFile').value.trim();
      const tokenFile = document.getElementById('tokenFile').value.trim();
      
      const categoryId = document.getElementById('categoryId').value.trim();
      const privacyStatus = document.getElementById('privacyStatus').value;
      const madeForKids = document.getElementById('madeForKids').checked;
      const uploadDesc = document.getElementById('uploadDesc').value.split('\n').map(s => s.trim()).filter(s => s);
      const uploadTags = document.getElementById('uploadTags').value.split(',').map(s => s.trim()).filter(s => s);
      const defaultLang = document.getElementById('defaultLang').value.trim();
      const minHours = parseInt(document.getElementById('minHours').value) || 4;

      // Collect schedule entries
      const scheduleEditor = document.getElementById('scheduleEditor');
      const schedules = [];
      if (scheduleEditor) {
        const wrappers = scheduleEditor.children;
        for (let i = 0; i < wrappers.length; i++) {
          const hourInput = document.getElementById(`hour_${i}`);
          const startInput = document.getElementById(`start_${i}`);
          const endInput = document.getElementById(`end_${i}`);
          
          if (hourInput && startInput && endInput) {
            const hour = parseInt(hourInput.value) || 0;
            const start = parseInt(startInput.value) || 0;
            const end = parseInt(endInput.value) || 0;
            schedules.push({ hour, minute_start: start, minute_end: end });
          }
        }
      }

      const settings = {
        name: name,
        description: description,
        enabled: enabled,
        video_directory: videoDir,
        titles_file: titlesFile,
        token_file: tokenFile,
        upload_settings: {
          category_id: categoryId,
          privacy_status: privacyStatus,
          made_for_kids: madeForKids,
          description: uploadDesc,
          tags: uploadTags,
          default_language: defaultLang
        },
        min_hours_between_uploads: minHours,
        upload_schedule: schedules
      };

      const formData = new FormData();
      formData.append('action', 'update_settings');
      formData.append('channel', channelId);
      formData.append('settings', JSON.stringify(settings));

      fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            closeModal('settingsModal');
            showResults('Success', 'All channel settings have been updated successfully!');
            setTimeout(() => refreshDashboard(), 1500);
          } else {
            showResults('Error', 'Failed to save settings. Please try again.');
          }
        })
        .catch(e => showResults('Error', 'Network error: ' + e.message));
    }
  </script>
  
  
  
  
  <script>
document.addEventListener("DOMContentLoaded", function() {
    let body = document.body;
    let childNodes = Array.from(body.childNodes);
    childNodes.forEach(node => {
        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().startsWith("[")) {
            node.remove();
        }
    });
});
</script>




</body>
</html>

<?php
function getChannelYouTubeInfo($channelId) {
    global $config;
    
    if (!isset($config['channels'][$channelId])) {
        return null;
    }
    
    $channel = $config['channels'][$channelId];
    
    if (!file_exists($channel['token_file'])) {
        return null;
    }
    
    try {
        $client = new Client();
        $client->setClientId($config['google']['client_id']);
        $client->setClientSecret($config['google']['client_secret']);
        
        $tokenData = json_decode(file_get_contents($channel['token_file']), true);
        $client->setAccessToken($tokenData);
        
        if ($client->isAccessTokenExpired()) {
            return null;
        }
        
        $youtube = new YouTube($client);
        $channels = $youtube->channels->listChannels('snippet,statistics', ['mine' => true]);
        
        if (count($channels->getItems()) > 0) {
            $ytChannel = $channels->getItems()[0];
            return [
                'name' => $ytChannel->getSnippet()->getTitle(),
                'id' => $ytChannel->getId(),
                'subscribers' => $ytChannel->getStatistics()->getSubscriberCount(),
                'videos' => $ytChannel->getStatistics()->getVideoCount(),
            ];
        }
        
    } catch (Exception $e) {
        error_log("Failed to get YouTube info for channel $channelId: " . $e->getMessage());
    }
    
    return null;
}
?>