<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReconXi Newsletter - {{ ucfirst($status) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
        }
        .status-icon {
            font-size: 64px;
            margin: 20px 0;
        }
        .success { color: #2c664f; }
        .error { color: #d32f2f; }
        .invalid { color: #f57c00; }
        .message {
            margin: 20px 0;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #2c664f;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://api-dev.reconxi.com/assets/logo.png" alt="ReconXi Logo" height="40">
        </div>
        
        <div class="status-icon {{ $status }}">
            @if($status == 'success')
                ✓
            @elseif($status == 'invalid')
                ⚠
            @else
                ✗
            @endif
        </div>
        
        <h1>
            @if($action == 'resubscribe')
                Resubscription {{ ucfirst($status) }}
            @else
                Unsubscription {{ ucfirst($status) }}
            @endif
        </h1>

        <div class="message">
            <p>{{ $message }}</p>
        </div>

        <a href="https://reconxi.com" class="button">Go to Homepage</a>
    </div>
</body>
</html>