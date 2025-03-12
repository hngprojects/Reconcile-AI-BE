<!-- (NI- No Image) email-verification -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ReconXi</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');
        body {
            font-family: "Inter", sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #FFFFFF;
        }
        
        .header {
            background-color: #EAEFED;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        
        .logo {
            height: 40px;
        }
        
        .main-content {
            width: 100%;
            background-color: #FFFFFF;
            padding: 40px;
            box-sizing: border-box;
        }
        
        .content-container {
            max-width: 680px;
            margin: 0 auto;
        }
        
        .page-title {
            font-size: 24px;
            color: #333333;
            margin-top: 0;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 600;
        }
        
        .greeting {
            font-size: 20px;
            color: #333333;
            margin-top: 10%;
            margin-bottom: 6%;
            font-weight: bold;
        }
        
        .message {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .expiry-notice {
            font-size: 16px;
            color: #333333;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .instruction {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .verify-button {
            display: inline-block;
            background-color: #2E604A !important;
            color: white !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .verify-button:hover {
            background-color: #489473;
        }
        
        .link-text {
            font-size: 16px;
            color: #333333;
            margin-bottom: 10px;
        }
        
        .verification-link {
            color: #2E604A;
            text-decoration: none;
            word-break: break-all;
            font-size: 14px;
        }
        
        .signature {
            margin-top: 10%;
            font-size: 14px;
            color: #333333;
        }
        
        .footer {
            background-color: #EAEFED;
            padding: 30px 0;
            text-align: center;
            margin-top: auto;
            width: 100%;
        }
        
        .social-icons {
            margin-bottom: 20px;
        }
        
        .social-icons a {
            margin: 0 10px;
            text-decoration: none;
        }
        
        .social-icon {
            width: 24px;
            height: 24px;
            fill: #5B5B5D;
        }
        
        .footer-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .footer-link {
            color: #111111;
            text-decoration: none;
            font-weight: bold;
        }
        
        .divider {
            height: 1px;
            background-color: #DDD;
            margin: 20px auto;
            max-width: 600px;
        }
        
        .footer-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }
        
        .preference-links {
            font-size: 14px;
            color: #666;
        }
        
        .preference-link {
            color: #111111;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ config('app.url') }}/assets/logo..svg" alt="ReconXi Logo" class="logo">
    </div>
    
    <div class="main-content">
        <h1 class="page-title">Hi {{ $user->name }}</h1>
        <p class="instruction">Welcome To ReconXi!</p>


        <div class="content-container"> 
            <p class="message">We know how challenging it can be to manage transactions, and ensure financial accuracy.</p>
            <p class="expiry-notice">
                <ul>
                    <li>Here’s what you can expect:</li>
                    <li>AI-powered reconciliation</li>
                    <li>Easy to use reconciled exports</li>
                </ul>
            </p>
            <p class="instruction">Start Reconciling Now</p>
            <div class="button-container">
                <a href="{{ $getStartedUrl }}" class="verify-button">Get Started</a>
            </div>

            <p class="link-text">
                Need help? Our support team is just an email away at 
                <a href="mailto:support@reconxi.com">Support Team</a>.
            </p>

            <div class="signature">
                <p>Cheers,</p>
                <p>The ReconXi Team</p>
            </div>
        </div>
    </div>
    
    <div class="footer">
    <div class="social-icons">
            <a href="https://www.instagram.com/reconxi02/?igsh=YTh5aWx6Y2c2dW0w#">
                <img src="{{ config('app.url') }}/assets/instagram-icon..svg" alt="Instagram" class="social-icon">
            </a>
            <a href="https://www.facebook.com/profile.php?id=61573471907361&mibextid=rS40aB7S9Ucbxw6v">
                <img src="{{ config('app.url') }}/assets/facebook-icon..svg" alt="Facebook" class="social-icon">
            </a>
            <a href="https://www.linkedin.com/in/recon-xi-b06835354">
                <img src="{{ config('app.url') }}/assets/linkedin-icon..svg" alt="Linkedin" class="social-icon">
            </a>
            <a href="https://x.com/reconxi02?s=21&t=6GEcIpxFOrczvmtrZsCzSw">
                <img src="{{ config('app.url') }}/assets/twitter-icon..svg" alt="Twitter" class="social-icon">
            </a>
        </div>
        
        <div class="divider"></div>
        
        <p class="footer-message">You are receiving this email because you signed up at Reconxi.com. Want to change how you receive these emails?</p>
    </div>
</body>
</html>
