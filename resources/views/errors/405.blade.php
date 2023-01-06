<html>
    <head></head>
    <body style="height: 100vh; width: 100vw; background: #0e101f; padding: 4rem; font-family: Montserrat, sans-serif;">
        <h1 style="color:white">405 Method Not Allowed</h1>
        <a href="https://game.iceline.host" style="color: #0550b3; text-decoration: none; font-weight: 600">Return to the Game Panel</a>
        <script>
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());

            for (const key in params) {
                // Workaround for invalid cloudflare captcha redirect
                if (key.startsWith("__cf")) {
                    var url = [location.protocol, '//', location.host, location.pathname].join('');
                    window.location.replace(url)
                }
            }
        </script>
    </body>
</html>
