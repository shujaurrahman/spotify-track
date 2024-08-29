<?php
require '../vendor/autoload.php'; // Use dotenv for environment variables

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$clientId = $_ENV['SPOTIFY_CLIENT_ID'];
$clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$tokenFilePath = '../tokens.json'; // Path to the JSON file storing tokens

// Fetch tokens from JSON file
function fetchTokensFromJson() {
    global $tokenFilePath;
    return file_exists($tokenFilePath) ? json_decode(file_get_contents($tokenFilePath), true) : [];
}

// Refresh Spotify access token
function refreshAccessToken($refreshToken) {
    global $clientId, $clientSecret;

    $auth = base64_encode("$clientId:$clientSecret");
    $options = [
        'http' => [
            'header' => "Authorization: Basic $auth\r\nContent-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]),
            'ignore_errors' => true,
        ]
    ];
    $response = file_get_contents('https://accounts.spotify.com/api/token', false, stream_context_create($options));
    return $response !== FALSE ? json_decode($response, true) : [];
}

// Save tokens to JSON file
function saveTokensToJson($tokenData) {
    global $tokenFilePath;
    file_put_contents($tokenFilePath, json_encode([
        'access_token' => $tokenData['access_token'],
        'refresh_token' => $tokenData['refresh_token'] ?? null,
        'expires_in' => time() + $tokenData['expires_in'],
    ]));
}

// Fetch currently playing or last played track
function fetchTrack($accessToken) {
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer $accessToken",
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);

    // Attempt to get the currently playing track
    $response = file_get_contents('https://api.spotify.com/v1/me/player/currently-playing', false, $context);

    // If no currently playing track, get the last played track
    if ($response === FALSE || empty($response)) {
        $response = file_get_contents('https://api.spotify.com/v1/me/player/recently-played?limit=1', false, $context);
    }

    if ($response === FALSE) {
        echo '<p>Unable to fetch track information.</p>';
        return;
    }

    $data = json_decode($response);
    $track = $data->item ?? ($data->items[0]->track ?? null);

    if ($track) {
        displayTrack($track, isset($data->item));
    } else {
        echo '<p>No track information available.</p>';
    }
}

// Display track information
function displayTrack($track, $isCurrentlyPlaying) {
    $headerText = $isCurrentlyPlaying ? 'Now Playing' : 'Last Played';
    $trackName = htmlspecialchars($track->name);
    $artistName = htmlspecialchars($track->artists[0]->name);
    $albumName = htmlspecialchars($track->album->name);
    $imageUrl = htmlspecialchars($track->album->images[0]->url);
    $trackUrl = htmlspecialchars($track->external_urls->spotify);

    echo "<h4 class='spotify-header'>
            <svg xmlns='http://www.w3.org/2000/svg' width='20' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                <path d='M3 18v-6a9 9 0 0 1 18 0v6'></path>
                <path d='M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-7'></path>
                <path d='M7 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7'></path>
            </svg> $headerText
        </h4>
        <a href='$trackUrl' target='_blank'>
            <div class='track-card'>
                <img src='$imageUrl' alt='Album Cover'>
                <div class='track-info'>
                    <div class='track-title'>$trackName by $artistName</div>
                    <div class='track-album'>Album: $albumName</div>
                </div>
            </div>
        </a>";
}

// Main flow
$tokens = fetchTokensFromJson();
$accessToken = $tokens['access_token'] ?? null;
$refreshToken = $tokens['refresh_token'] ?? null;

if (!$accessToken || ($tokens['expires_in'] ?? 0) < time()) {
    $tokenData = refreshAccessToken($refreshToken);
    if (!empty($tokenData)) {
        saveTokensToJson($tokenData);
        $accessToken = $tokenData['access_token'];
    }
}

if ($accessToken) {
    fetchTrack($accessToken);
} else {
    echo '<p>Access token not available.</p>';
}
?>
