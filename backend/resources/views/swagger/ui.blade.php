<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Securecy LMS API Documentation" />
    <title>Securecy LMS API Documentation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Roboto", sans-serif;
            background: #fafafa;
        }
        .topbar {
            background-color: #1e90ff;
            padding: 10px 0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
        }
        .topbar-title {
            color: white;
            margin: 0;
            padding: 10px 20px;
            font-size: 18px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1 class="topbar-title">🎓 Securecy LMS API Documentation</h1>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui-bundle.js"></script>
    <script>
        const ui = SwaggerUIBundle({
            url: "{{ url('/api/docs.json') }}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset
            ],
            layout: 'BaseLayout',
            onComplete: function() {
                console.log('Swagger UI loaded');
            }
        });
        window.ui = ui;
    </script>
</body>
</html>
