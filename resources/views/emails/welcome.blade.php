<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ReconXi</title>
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
        .button {
            display: inline-block;
            background-color: #2c664f !important;
            color: #ffffff !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://api-dev.reconxi.com/assets/logo.png" alt="ReconXi Logo" height="40">
        </div>
        
        <div class="content">
            <img src="https://api-dev.reconxi.com/assets/welcome-illustration.png" alt="Welcome" width="200">
            <h2>Welcome to ReconXi</h2>
            <p>Thanks for signing up</p>
            
            <div class="text-content">
                <h3>Hi {{ $user->name }}</h3>
                <p>We know how challenging it can be to manage transactions, and ensure financial accuracy.</p>
                
                <h4>Here's what you can expect:</h4>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Ai Powered Reconciliation:</strong> Save hours through Ai powered repetitive reconciliation processes.
                    </div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Easy to use Reconciled Reports:</strong> Minimize human mistakes with intelligent matching and verification.
                    </div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Need Assistance?</strong> Our support team is here for you! If you have any questions, simply reply to this mail or contact us at <a href="mailto:support@reconxi.com">Support Team</a>.
                    </div>
                </div>
            </div>
            
            <a href="{{ $getStartedUrl }}" class="button">Start Reconciling Now</a>
            
            <p>Best regards,<br>The ReconXi Team</p>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="https://www.instagram.com/reconxi02"><img src="https://api-dev.reconxi.com/assets/instagram-icon.png" alt="Instagram" width="24"></a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361"><img src="https://api-dev.reconxi.com/assets/facebook-icon.png" alt="Facebook" width="24"></a>
                <a href="https://www.linkedin.com/in/recon-xi-b06835354"><img src="https://api-dev.reconxi.com/assets/linkedin-icon.png" alt="LinkedIn" width="24"></a>
                <a href="https://x.com/reconxi02"><img src="https://api-dev.reconxi.com/assets/twitter-icon.png" alt="Twitter" width="24"></a>
            </div>
            
            <p>Thank you for choosing "ReconXi". Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
            
            <div class="divider"></div>
            
            <p>You are receiving this email because you signed up at <a href="https://reconxi.com/">ReconXi.com</a>. Want to change how you receive these emails?</p>
            <p>You can <a href="{{ url('api/v1/newsletter/unsubscribe/' . $user->email) }}">unsubscribe</a> from this list.</p>
        </div>
    </div>
</body>
</html>