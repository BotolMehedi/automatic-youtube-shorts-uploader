<h1 align="center">Automatic YouTube Shorts Uploader</h1>

<div align="center">


[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![YouTube API](https://img.shields.io/badge/YouTube-API%20v3-red?style=for-the-badge&logo=youtube&logoColor=white)](https://developers.google.com/youtube/v3)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-brightgreen?style=for-the-badge)](https://github.com/BotolMehedi/youtube-shorts-multi-uploader)
[![Email](https://img.shields.io/badge/Email-D14836?style=for-the-badge&logo=gmail&logoColor=white)](mailto:hello@mehedi.fun)[![GitHub](https://img.shields.io/badge/BotolMehedi-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/BotolMehedi)

*Automatic YouTube Shorts Uploader is a PHP tool that helps you easily manage multiple YouTube channels. It automates Shorts uploads using the YouTube Data API. Simply set your videos, channel details, and upload schedule, and the tool will automatically publish your Shorts to your channels.*

[Features](#-features) • [Installation](#-installation) • [Configuration](#-configuration) • [Usage](#-usage) • [Contributing](#-contributing)
</div>

---

## 🎯 Why This Tool?

If you manage multiple YouTube Shorts channels but struggle to find the time, this tool is for you. I originally developed it to manage my YT channels, handling multiple uploads daily without the hassle. Now, I’ve made it public so anyone can easily manage their channels.

With this tool, adding a new channel takes just 5 minutes, and everything else runs on autopilot. It saves hours of repetitive work, allowing you to focus on creating content instead of manually uploading it😪

---

## ✨ Features

- **Unlimited Channels** – Add as many YouTube channels as you want
- **Independent Tokens** – Each channel has its own secure authentication
- **Upload Settings** – Each channel has its own Videos Titles, Tags, Video Descriptions and other settings.
- **Intelligent Scheduling** – Set different upload times for each channel
- **Auto Token Refresh** – Never worry about expired credentials

### 🎨 Dashboard
- **Real-Time Status** – See all channels at a glance
- **Visual Controls** – Enable/disable channels with one click
- **Schedule Editor** – Manage upload times through web interface
- **Title Manager** – Edit video titles of each channels. you can set titles for randomly pick or you can set titles for each videos
- **Log Viewer** – Built-in debugging and monitoring

**💡 Note:** Without some basic coding knowledge, you may find it difficult to use this tool properly.

---

## 🚀 Installation

### Prerequisites
- **PHP 7.4 or higher**
- **Composer**
- **Web server** (Apache/Nginx)
- **Google Cloud Project** with YouTube Data API v3 enabled

### Step 1: Clone the Repository

```bash
git clone https://github.com/BotolMehedi/automatic-youtube-shorts-uploader.git
cd automatic-youtube-shorts-uploader
```

### Step 2: Install Dependencies

```bash
composer install
```

That's it! Composer will automatically create all necessary directories.

---

## 🔑 Getting API Credentials

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Create Project"**
3. Name it something like "YouTube Uploader"
4. Click **"Create"**

### 2. Enable YouTube Data API v3

1. In your project, go to **"APIs & Services" → "Library"**
2. Search for **"YouTube Data API v3"**
3. Click on it and press **"Enable"**

### 3. Create OAuth 2.0 Credentials

1. Go to **"APIs & Services" → "Credentials"**
2. Click **"Create Credentials" → "OAuth client ID"**
3. If prompted, configure the consent screen:
   - Choose **"External"**
   - Fill in app name and your email
   - Add your domain
   - Add these scopes:
     - `https://www.googleapis.com/auth/youtube.upload`
     - `https://www.googleapis.com/auth/youtube.readonly`
4. For **"Application type"**, select **"Web application"**
5. Add this **Authorized redirect URI**:
   ```
   https://your-domain.com/oauth_handler.php
   ```
6. Click **"Create"**
7. Copy your **Client ID** and **Client Secret**

### 4. Important: Publishing Status

⚠️ If your app is in **"Testing"** mode, tokens expire after 7 days. For production:
1. Go to **"OAuth consent screen"**
2. Click **"Publish App"**
3. This prevents token expiration issues
4. If your app is in Testing mode, you must add all your channel emails as test users.

---

## ⚙️ Configuration

Open `config.php` and update these values:

```php
'google' => [
    'client_id' => 'YOUR_CLIENT_ID_HERE',
    'client_secret' => 'YOUR_CLIENT_SECRET_HERE',
    'redirect_uri' => 'https://your-domain.com/oauth_handler.php',
    'api_key' => 'YOUR_API_KEY_HERE',
],
```

### Adding New Channels

To add a new channel, open the dashboard and click ‘New Channel.’ Enter your channel information, including channel name, category ID, default language code, videos directory, titles file name, upload schedule, and other relevant settings. After saving, click ‘Authorize’ to authorize the channel. That’s it, your channel is ready to use.

### Category IDs

| ID | Category Name |
|----|----------------|
| 1 | Film & Animation |
| 2 | Autos & Vehicles |
| 10 | Music |
| 15 | Pets & Animals |
| 17 | Sports |
| 18 | Short Movies |
| 19 | Travel & Events |
| 20 | Gaming |
| 21 | Videoblogging |
| **22** | **People & Blogs** |
| 23 | Comedy |
| 24 | Entertainment |
| 25 | News & Politics |
| **26** | **Howto & Style** |
| 27 | Education |
| 28 | Science & Technology |
| 29 | Nonprofits & Activism |
| 30 | Movies |
| 31 | Anime / Animation |
| 32 | Action / Adventure |
| 33 | Classics |
| 34 | Comedy (Non-User-Uploaded) |
| 35 | Documentary |
| 36 | Drama |
| 37 | Family |
| 38 | Foreign |
| 39 | Horror |
| 40 | Sci-Fi / Fantasy |
| 41 | Thriller |
| 42 | Shorts |
| 43 | Shows |
| 44 | Trailers |


---

## 🎬 Usage

### Step 1: Add Videos

Place your video files in the channel's directory:

```bash
videos/
├── channel1/
│   ├── video1.mp4
│   ├── video2.mp4
│   └── video3.mp4
└── channel2/
    ├── video1.mp4
    └── video2.mp4
```

**Supported formats:** MP4, MOV, AVI, MKV, FLV, WMV

### Step 2: Add Titles

Go to the Dashboard, select a channel, and click ‘Edit Titles.’ You’ll see two modes: Random and Fixed.

- Random: Titles are picked randomly for each video. Enter one title per line if you choose this mode.

- Fixed: Each video gets a specific title. Use the following format:

```bash
Simple Title | video1.mp4
Test Title | video2.mp4
Funny Video | video3.mp4
```

In Fixed mode, you must type the video file name along with its corresponding title.

### Step 3: Schedule Setup

You need to set the time period during which your video should be uploaded. Times use the 24-hour format. For example, if you set 15:00 to 15:30, your video will be uploaded sometime within that time window.

### Step 4: Set Up Automation

Manually add to crontab:

```bash
# 🕒 3 times daily (9 AM, 3 PM, 9 PM)
0 9,15,21 * * * /usr/bin/php /aytsu/publish.php
```

---

## 🐛 Troubleshooting

###📍 "No Refresh Token" Error

**Solution:**
1. Go to [Google Account Permissions](https://myaccount.google.com/permissions)
2. Remove your app's permissions
3. Re-authorize the channel through the dashboard

###📍 Videos Not Uploading

**Check:**
- ✅ Channel is enabled in dashboard
- ✅ Current time is within upload schedule
- ✅ Minimum hours between uploads has passed
- ✅ Videos exist in the channel directory
- ✅ Titles exists and is not empty

###📍 Token Expired Issues

**If tokens expire too quickly:** Make sure your app is **"Published"** in Google Cloud Console

---

## 📊 Performance Tips

### Optimize Upload Schedules
- **Stagger upload times** between channels (avoid same windows)
- **Limit concurrent uploads** to 2-3 channels at once

### Manage API Quota
YouTube Data API has daily quotas. Each upload costs about **1600 quota units**.

**Default quota:** 10,000 units/day = ~6 uploads/day

**To increase quota:**
1. Go to Google Cloud Console
2. Request quota increase
3. Explain your use case

---

## 🤝 Contributing

I built this for myself, but I'd love to see what you can add! Here's how to contribute:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request🎯

<div align="center">

[Star](https://img.shields.io/github/stars/BotolMehedi/automatic-youtube-shorts-uploader?style=social) | [Issue](https://github.com/BotolMehedi/automatic-youtube-shorts-uploader/issues) | [Discussion](https://github.com/BotolMehedi/automatic-youtube-shorts-uploader/discussions)

</div>

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**TL;DR:** You can use this freely, modify it, sell it, whatever. Just don't blame me if something breaks!😪

---

<div align="center">

### 🌟 Star this repo if you find it helpful!

[Portfolio](https://mehedi.fun) | [Email](mailto:hello@mehedi.fun) | [Github](https://github.com/BotolMehedi)

**Made with ❤️ and lots of 💦 by [BotolMehedi](https://github.com/BotolMehedi)**

</div>