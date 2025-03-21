<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Reconciliation Results are Here!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5 !important;
            color: #333 !important;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff !important;
        }
        .header {
            background-color: #f5f5f5 !important;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px 30px;
            text-align: center;
        }
        .text-content {
            text-align: left;
            padding: 10px 0;
        }
        .footer {
            background-color: #f5f5f5 !important;
            padding: 20px;
            text-align: center;
        }
        .divider {
            border-top: 1px dashed #e0e0e0;
            margin: 20px 0;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            margin: 0 10px;
            text-decoration: none;
        }
        .feature {
            text-align: left;
            margin: 15px 0;
            display: flex;
            align-items: flex-start;
        }
        .feature-icon {
            margin-right: 10px;
            color: #2c664f !important;
        }
        /* Notification Styles */
        .notification {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #2c664f !important;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://api-dev.reconxi.com/assets/logo.png" alt="ReconXi Logo" height="40">
        </div>

        <div class="content">
            <img src="https://reconxi.com/reconResult.svg" alt="Your Reconciliation is Here!" width="200">
            <h2>Your Reconciliation Results are Ready</h2>

            <div class="text-content">
                <p>Dear {{$user->name}},</p>

                <p>Your reconciliation is complete and you can view the results <a href="{{$url}}">here</a></p>

            </div>
        </div>

        <div class="footer">
            <div class="social-links">
                <a href="https://instagram.com/reconxi"><img src="https://api-dev.reconxi.com/assets/instagram-icon.png" alt="Instagram" width="24"></a>
                <a href="https://facebook.com/reconxi"><img src="https://api-dev.reconxi.com/assets/facebook-icon.png" alt="Facebook" width="24"></a>
                <a href="https://linkedin.com/company/reconxi"><img src="https://api-dev.reconxi.com/assets/linkedin-icon.png" alt="LinkedIn" width="24"></a>
                <a href="https://twitter.com/reconxi"><img src="https://api-dev.reconxi.com/assets/twitter-icon.png" alt="Twitter" width="24"></a>
            </div>

            <p>Thank you for choosing ReconXi. Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
        </div>
    </div>
</body>
</html>
