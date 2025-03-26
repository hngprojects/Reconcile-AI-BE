<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReconXi - Partner Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #f5f5f5;
        }
        .header {
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .logo {
            max-width: 150px;
        }
        .illustration {
            text-align: center;
            padding: 15px 0;
        }
        .illustration img {
            max-width: 100px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
        }
        .footer {
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e0e0e0;
        }
        .social-icons {
            margin-bottom: 15px;
        }
        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
        }
        .social-icons img {
            width: 24px;
            height: 24px;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        h1 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
        }
        p {
            color: #555;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        .footer-text {
            font-size: 13px;
            color: #777;
            margin-top: 10px;
            line-height: 1.4;
        }
        .contact-link {
            color: #000;
            font-weight: bold;
            text-decoration: none;
        }
        .footer-link {
            color: #000;
            text-decoration: none;
            font-weight: bold;
        }
        @media only screen and (max-width: 480px) {
            .content {
                padding: 15px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('assets/logo.png')) }}" alt="ReconXi" class="logo">
        </div>
        
        <div class="illustration">
            <img src="{{ $message->embed(public_path('assets/partner.png')) }}" alt="Mobile Illustration">
        </div>
        
        <div class="content">
            <h1>Dear {{ $data->full_name }},</h1>
            
            <p>Thank you for reaching out to us and expressing your interest with ReconXi! We appreciate you taking the time to fill out our Partner with Us form. We are excited about the possibility of collaborating with you and exploring how we can work together to create value for our clients.</p>
            
            <p>Our team is currently reviewing your submission, and we will get back to you shortly to discuss potential partnership opportunities.</p>
            
            <p>In the meantime, if you have any questions or need further information, please feel free to reach out to us at <a href="mailto:support@reconxi.com">support email</a> or <a href="tel:">phone number</a></p>
            
            <p>Thank you once again for your interest. We look forward to connecting soon!</p>
            
            <p>Best regards,<br>
            Mercy<br>
            Team Lead<br>
            ReconXi</p>
        </div>
        
        <div class="footer">
            <div class="social-icons">
                <a href="https://www.instagram.com/reconxihq?igsh=MXd5a3Q2YmZrb2s5bg%3D%3D&utm_source=qr"><img src="{{ $message->embed(public_path('assets/instagram-icon.png')) }}" alt="Instagram"></a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361"><img src="{{ $message->embed(public_path('assets/facebook-icon.png')) }}" alt="Facebook"></a>
                <a href="http://www.linkedin.com/in/recon-xi-b06835354"><img src="{{ $message->embed(public_path('assets/linkedin-icon.png')) }}" alt="LinkedIn"></a>
                <a href="https://x.com/thereconxi"><img src="{{ $message->embed(public_path('assets/twitter-icon.png')) }}" alt="Twitter"></a>
            </div>
            
            <p>Thank you for choosing ReconXi. Need help? <a href="mailto:support@reconxi.com" class="contact-link">Contact us</a></p>
            
            <div class="divider"></div>
            
            <p class="footer-text">You are receiving this email because you signed up at <a href="https://reconxi.com/">ReconXi.com</a>. Want to change how you receive these emails?</p>
            
            <p class="footer-text">You can <a href="{{ route('newsletter.one-click-unsubscribe', ['email' => $data->email]) }}" class="footer-link">unsubscribe from this list</a>.</p>
        </div>
    </div>
</body>
</html>