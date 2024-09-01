<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Authorize Link -->
        <a href="access.php" class="authorize-link">Authorize [Click, Only if shows below token not fetched.]</a>

        <div class="spotify">
            <!-- Empty container to display the currently playing track -->
            <div id="spotify-player"></div>
        </div>
        <a href="https://blog-shujaurrahman.vercel.app/post.html?postUrl=.%2Fposts%2F2024-08-30.md&postTitle=Integrate%20Spotify%20with%20Your%20Website%3A%20A%20Comprehensive%20Guide" class="authorize-link">Blog : How to display Spotify Track </a>
    </div>

    <!-- JavaScript for auto-updating the content every 30 seconds -->
    <script>
        function updateSpotifyPlayer() {
            // Fetch the content from the PHP handler and update the #spotify-player container
            fetch('./includes/spotify_handler.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('spotify-player').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching currently playing track:', error);
                });
        }

        // Initial load
        updateSpotifyPlayer();

        // Set up the interval to refresh every 30 seconds (30000 milliseconds)
        setInterval(updateSpotifyPlayer, 300000);
    </script>
</body>
</html>
