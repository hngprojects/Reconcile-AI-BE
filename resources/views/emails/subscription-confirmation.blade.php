<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Subscribing to ReconXi</title>
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
            <img src="{{ $message->embed(public_path('assets/logo.png')) }}" alt="ReconXi Logo" height="40">
        </div>
        
        <div class="content">
            <img src="{{ $message->embed(public_path('assets/thank-you-illustration.png')) }}" alt="Thank You" width="200">
            <h2>Thank you for Subscribing</h2>
            
            <div class="text-content">
                <p>Dear Subcriber,</p>
                
                <p>Thank you for subscribing to ReconXI, your AI-driven solution for seamless bank reconciliation. We're excited to have you on board and look forward to helping you streamline your financial processes.</p>
                
                <h4>As a subscriber, you'll receive:</h4>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Exclusive Updates:</strong> Stay informed about the latest features and enhancements in AI-powered bank reconciliation.
                    </div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Industry Insights:</strong> Gain access to expert articles and best practices in financial automation.
                    </div>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">★</div>
                    <div>
                        <strong>Special Offers:</strong> Be the first to know about promotions and events.
                    </div>
                </div>
                
                <p>We value your privacy and will ensure that our content is relevant and valuable to you. If you have any questions or feedback, please don't hesitate to reach out.</p>
                <p>Welcome aboard!</p>
                
                <p>Best regards,<br>The ReconXi Team</p>
            </div>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="https://instagram.com/reconxi"><img src="{{ $message->embed(public_path('assets/instagram-icon.png')) }}" alt="Instagram" width="24"></a>
                <a href="https://facebook.com/reconxi"><img src="{{ $message->embed(public_path('assets/facebook-icon.png')) }}" alt="Facebook" width="24"></a>
                <a href="https://linkedin.com/company/reconxi"><img src="{{ $message->embed(public_path('assets/linkedin-icon.png')) }}" alt="LinkedIn" width="24"></a>
                <a href="https://twitter.com/reconxi"><img src="{{ $message->embed(public_path('assets/twitter-icon.png')) }}" alt="Twitter" width="24"></a>
            </div>
            
            <p>Thank you for choosing ReconXi. Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
            
            <div class="divider"></div>
            
            <p>You are receiving this email because you signed up at <a href="https://reconxi.com/">ReconXi.com</a>. Want to change how you receive these emails?</p>
            <p>You can <a href="{{ url('api/v1/newsletter/unsubscribe/' . $email) }}">unsubscribe</a> from this list.</p>
        </div>
    </div>
</body>
</html>