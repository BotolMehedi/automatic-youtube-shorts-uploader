<?php
/**
 * OAuth Handler ::::: YouTube Shorts Uploader
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

$config = require 'config.php';
$channelsFile = __DIR__ . '/channels.json';

// Create Folders
$directories = [$config['global']['tokens_directory'], $config['global']['logs_directory'], 'titles'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0755, true);
}

// FormDatas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_channel_submit'])) {
    $channels = json_decode(file_get_contents($channelsFile), true);
    $newId = trim($_POST['channel_id']);
    if (!isset($channels[$newId])) {
        $channels[$newId] = [
            "name" => $_POST['channel_name'],
            "description" => $_POST['description'] ?? "",
            "video_directory" => $_POST['video_directory'] ?? "",
            "titles_file" => $_POST['titles_file'] ?? "",
            "token_file" => "tokens/{$newId}.json",
            "enabled" => isset($_POST['enabled']),
            "upload_settings" => [
                "category_id" => $_POST['category_id'] ?? "26",
                "privacy_status" => $_POST['privacy_status'] ?? "public",
                "made_for_kids" => isset($_POST['made_for_kids']),
                "description" => [$_POST['yt_description'] ?? ""],
                "tags" => array_map('trim', explode(",", $_POST['tags'] ?? "")),
                "default_language" => $_POST['default_language'] ?? "en"
            ],
            "upload_schedule" => [],
            "min_hours_between_uploads" => intval($_POST['min_hours_between_uploads'] ?? 4)
        ];
        file_put_contents($channelsFile, json_encode($channels, JSON_PRETTY_PRINT));
        header("Location: ".$_SERVER['PHP_SELF']); exit;
    }
}

// Get Parameters
$channelId = $_GET['channel'] ?? null;
$authCode = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;

// OAuth errors
if ($error) {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545; margin: 20px;'>";
    echo "<h2>❌ Authorization Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>";
    if (isset($_GET['error_description'])) echo "<p><strong>Description:</strong> " . htmlspecialchars($_GET['error_description']) . "</p>";
    echo "<p><a href='?'>🔄 Try Again</a></p></div>"; exit;
}

// OAuth callback
if ($authCode && $channelId) { handleOAuthCallback($authCode, $channelId, $config); exit; }


if (!$channelId) { showChannelSelection($config); } else { handleTokenCreation($channelId, $config); }

function showChannelSelection($config) {
    global $channelsFile;
    $channels = json_decode(file_get_contents($channelsFile), true);

    // Datas
    $channelStatuses = [];
    foreach ($channels as $id => $channel) {
        $channelStatuses[$id] = [
            'id' => $id,
            'name' => $channel['name'],
            'authorized' => file_exists($channel['token_file']),
            'enabled' => $channel['enabled'],
            'videos_available' => is_dir($channel['video_directory']) ? count(glob($channel['video_directory'].'*')) : 0,
            'titles_available' => file_exists($channel['titles_file']) 
    ? count(json_decode(file_get_contents($channel['titles_file']), true)['titles']) 
    : 0,

            'last_upload' => $channel['last_upload'] ?? 'Never',
            'next_upload_windows' => ['N/A']
        ];
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>OAuth Handler | Automatic YouTube Shorts Uploader</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-gray-100 text-xs antialiased">
      <div class="max-w-7xl mx-auto p-4 md:p-6">
        <!-- Header -->
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
          <!-- Sidebar: Quick Actions + Logs -->
          <aside class="lg:col-span-1 space-y-4">
            <div class="rounded-xl bg-[var(--glass)] p-3 shadow-md border border-white/5">
              <h3 class="font-semibold mb-2">Quick Actions</h3>
              <div class="flex flex-col gap-2">
                <button onclick="window.location.reload()" class="w-full py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-medium">🔄 Refresh</button>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="w-full py-2 rounded-md bg-amber-400 hover:bg-amber-500 text-slate-900 font-medium">➕ Add New Channel</button>
                <a href="logs/yt_shorts_uploader.log" target="_blank" class="w-full block text-center py-2 rounded-md bg-gray-700 hover:bg-gray-600 text-white font-medium">📋 View Logs</a>
              </div>
            </div>
          </aside>

          <!-- Channels Grid -->
          <section class="lg:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
              <?php foreach ($channelStatuses as $id => $status): ?>
                <div id="channel-<?= $id ?>" class="rounded-2xl bg-gradient-to-b from-white/3 to-white/2 p-3 shadow-lg border border-white/6">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <div class="w-10 h-10 rounded-lg bg-slate-600 flex items-center justify-center text-white font-bold"><?= strtoupper(substr($status['name'],0,2)) ?></div>
                      <div>
                        <div class="font-semibold"><?= htmlspecialchars($status['name']) ?></div>
                        <div class="text-xxs text-gray-300"><?= htmlspecialchars($status['id'] ?? $id) ?></div>
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
                      <div class="font-semibold"><?= $status['last_upload'] ?></div>
                    </div>
                    <div class="bg-white/5 p-2 rounded">
                      <div class="text-gray-300">🎯 Next Time</div>
                      <div class="font-semibold"><?= implode(', ', $status['next_upload_windows']) ?></div>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2 mt-3">
                    <?php if ($status['authorized']): ?>
                      <a href="?channel=<?= $id ?>&action=reauth" class="flex-1 min-w-[90px] py-2 rounded-md bg-amber-400 hover:bg-amber-500 text-slate-900 text-center">🔄 Re-authorize</a>
                    <?php else: ?>
                      <a href="?channel=<?= $id ?>" class="flex-1 min-w-[90px] py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white text-center">🔐 Authorize</a>
                    <?php endif; ?>
                    <a href="dashboard.php?channel=<?= $id ?>" class="py-2 px-3 rounded-md bg-slate-700 hover:bg-slate-600 text-white">⚙️ Manage</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        </main>

        <footer class="mt-6 text-center text-gray-400 text-xxs">
          AYTSU | v1.0.1
        </footer>
      </div>

      <!-- Add New Modal -->
      <div id="addModal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 hidden z-50">
          <div class="bg-white/10 border border-white/10 backdrop-blur-md rounded-3xl p-6 w-full max-w-lg relative overflow-y-auto max-h-[90vh]">
              <button onclick="document.getElementById('addModal').classList.add('hidden')" class="absolute top-3 right-3 text-white text-2xl">&times;</button>
              <h2 class="text-2xl font-bold mb-4 text-center">➕ Add New Channel</h2>
              <form method="post" class="space-y-3">
                  
                  <div class="grid grid-cols-2 gap-3">
                      <input type="text" name="channel_id" id="channelIdInput" placeholder="Channel ID" required class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                      <input type="text" name="channel_name" placeholder="Channel Name" required class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                  </div>
                  <input type="text" name="description" value="Funny Channel" placeholder="Description" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                  <div class="grid grid-cols-2 gap-3">
                      <input type="text" name="video_directory" value="videos/channel1/" placeholder="Video Directory" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                      <input type="text" name="titles_file" value="titles/channel1_title.json" placeholder="Titles File" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                  </div>
                  <input type="text" name="tags" value="shorts, viral, reels, fun," placeholder="Tags (comma separated)" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                  <div class="flex gap-4 items-center">
                      <label class="flex items-center gap-2"><input type="checkbox" name="enabled"> Enabled</label>
                      <label class="flex items-center gap-2"><input type="checkbox" name="made_for_kids"> Made for Kids</label>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                      <input type="text" name="category_id" value="26"  placeholder="Category ID" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                      <select name="privacy_status" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                          <option value="public">Public</option>
                          <option value="private">Private</option>
                          <option value="unlisted">Unlisted</option>
                      </select>
                  </div>
                  <textarea name="yt_description" placeholder="Video Description" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">Like Comment Share Subscribe #viral #fyp</textarea>
                  <div class="grid grid-cols-2 gap-3">
                      <input type="text" name="default_language" value="en" placeholder="Default Language" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                      <input type="number" name="min_hours_between_uploads" value="4" placeholder="Min Hours Between Uploads" class="w-full p-2 rounded-lg bg-white/10 text-white border border-white/20">
                  </div>
                  <button type="submit" name="new_channel_submit" class="w-full py-2 bg-green-600 hover:bg-green-500 rounded-xl font-semibold text-lg">Add Channel</button>
              </form>
          </div>
      </div>
      
      
      <script>
const addModal = document.getElementById('addModal');
const channelInput = document.getElementById('channelIdInput');

function randomId(length = 6) {
    return Math.random().toString(36).substring(2, 2 + length).toUpperCase();
}

const observer = new MutationObserver(() => {
    if (!addModal.classList.contains('hidden')) {
        channelInput.value = randomId();
    }
});

observer.observe(addModal, { attributes: true, attributeFilter: ['class'] });
</script>

    </body>
    </html>
    <?php
}


function handleTokenCreation($channelId, $config) {
    
    $channel = $config['channels'][$channelId] ?? die("Invalid channel ID: $channelId");

    $client = new Client();
    $client->setClientId($config['google']['client_id']);
    $client->setClientSecret($config['google']['client_secret']);
    $client->setRedirectUri($config['google']['redirect_uri']);
    $client->addScope(YouTube::YOUTUBE_UPLOAD);
    $client->addScope(YouTube::YOUTUBE_READONLY);
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $client->setIncludeGrantedScopes(true);
    $client->setState($channelId);

    $isReauth = isset($_GET['action']) && $_GET['action'] === 'reauth';
    if ($isReauth && file_exists($channel['token_file'])) {
        $backupFile = $channel['token_file'] . '.backup.' . date('Y-m-d-H-i-s');
        copy($channel['token_file'], $backupFile);
    }

    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}

function handleOAuthCallback($authCode, $channelId, $config) {
    $channel = $config['channels'][$channelId] ?? die("Invalid channel ID during callback: $channelId");
    try {
        $client = new Client();
        $client->setClientId($config['google']['client_id']);
        $client->setClientSecret($config['google']['client_secret']);
        $client->setRedirectUri($config['google']['redirect_uri']);
        $client->addScope(YouTube::YOUTUBE_UPLOAD);
        $client->addScope(YouTube::YOUTUBE_READONLY);
        $client->setAccessType('offline');

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        if (isset($accessToken['error'])) throw new Exception('Token exchange failed: '.$accessToken['error_description']);
        file_put_contents($channel['token_file'], json_encode($accessToken));

        $client->setAccessToken($accessToken);
        $youtube = new YouTube($client);
        try { $youtube->channels->listChannels('snippet', ['mine'=>true]); } catch(Exception $e){}

        echo "<h2>✅ Setup Complete for " . htmlspecialchars($channel['name']) . "</h2>";
        echo "<p><a href='dashboard.php?channel=$channelId'>⚙️ Manage Channel</a> | <a href='?'>🔄 Back to List</a></p>";
    } catch(Exception $e){
        echo "<h2>❌ Setup Failed</h2><p>Error: ".htmlspecialchars($e->getMessage())."</p>";
    }
}
?>
