<!DOCTYPE html>
<html>
<head>
    <title>Google OAuth Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 10px 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Google OAuth Configuration Test</h1>
    
    <div id="results"></div>
    
    <button onclick="testConfig()">Test Configuration</button>
    <button onclick="testGoogleAPI()">Test Google API</button>
    
    <script>
        async function testConfig() {
            const results = document.getElementById('results');
            results.innerHTML = '<div class="info">Testing configuration...</div>';
            
            try {
                const response = await fetch('get-google-config.php');
                const data = await response.json();
                
                if (data.success) {
                    results.innerHTML = `
                        <div class="success">
                            <strong>✓ Configuration Found!</strong><br>
                            Client ID: ${data.client_id.substring(0, 30)}...
                        </div>
                    `;
                } else {
                    results.innerHTML = `
                        <div class="error">
                            <strong>✗ Configuration Error</strong><br>
                            ${data.message}<br>
                            <pre>${JSON.stringify(data.debug || {}, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                results.innerHTML = `
                    <div class="error">
                        <strong>✗ Request Failed</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
        
        function testGoogleAPI() {
            const results = document.getElementById('results');
            results.innerHTML = '<div class="info">Testing Google API...</div>';
            
            if (typeof google === 'undefined') {
                results.innerHTML = `
                    <div class="error">
                        <strong>✗ Google API Not Loaded</strong><br>
                        Make sure the Google Identity Services script is loaded:<br>
                        &lt;script src="https://accounts.google.com/gsi/client" async defer&gt;&lt;/script&gt;
                    </div>
                `;
                return;
            }
            
            if (!google.accounts || !google.accounts.oauth2) {
                results.innerHTML = `
                    <div class="error">
                        <strong>✗ Google OAuth2 Not Available</strong><br>
                        Google Identity Services loaded but OAuth2 is not available.
                    </div>
                `;
                return;
            }
            
            results.innerHTML = `
                <div class="success">
                    <strong>✓ Google API Loaded Successfully!</strong><br>
                    Google Identity Services is ready to use.
                </div>
            `;
        }
        
        // Auto-test on load
        window.addEventListener('load', function() {
            testConfig();
            setTimeout(testGoogleAPI, 1000);
        });
    </script>
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>

