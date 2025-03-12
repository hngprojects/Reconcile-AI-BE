<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Enquiry - ReconXi</title>
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF;
            color: #333333;
            line-height: 1.6;
        }
        
        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Header section */
        .header {
            background-color: #EAEFED;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        
        .logo {
            max-width: 150px;
            height: auto;
        }
        
        /* Content section */
        .content {
            padding: 30px;
            background-color: #FFFFFF;
        }
        
        .illustration {
            text-align: center;
            margin: 20px 0;
        }
        
        .illustration img {
            max-width: 200px;
            height: auto;
        }
        
        /* Message box */
        .message-box {
            border: 1px solid #E0E0E0;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
            background-color: #FAFAFA;
        }
        
        .message-details {
            margin-bottom: 10px;
        }
        
        .message-content {
            white-space: pre-line;
        }
        
        /* Footer section */
        .footer {
            background-color: #EAEFED;
            padding: 20px 0;
            text-align: center;
            width: 100%;
            font-size: 14px;
            color: #666666;
        }
        
        .social-icons {
            margin: 15px 0;
        }
        
        .social-icons a {
            display: inline-block;
            margin: 0 8px;
            text-decoration: none;
        }
        
        .social-icons img {
            width: 24px;
            height: 24px;
        }
        
        .divider {
            border-top: 1px dashed #CCCCCC;
            margin: 20px 0;
        }
        
        .footer-links a {
            color: #2E604A;
            text-decoration: none;
            font-weight: bold;
        }
        
        /* Utility */
        .mt-20 {
            margin-top: 20px;
        }
        
        /* Media Queries */
        @media only screen and (max-width: 480px) {
            .content {
                padding: 15px;
            }
            
            .message-box {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ config('app.url') }}/assets/logo.svg" alt="ReconXi Logo" class="logo">
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="illustration">
                <img src="{{ config('app.url') }}/assets/message-received.svg" alt="Email Notification">
            </div>
            
            <h2>Hello Admin</h2>
            
            <p>A new enquiry has been submitted via the Contact Us form on our application. Please find the details below:</p>
            
            <div class="message-box">
                <div class="message-details">
                    <strong>Name:</strong> {{ $data['name'] }}
                </div>
                
                <div class="message-details">
                    <strong>Email:</strong> {{ $data['email'] }}
                </div>
                
                <div class="message-details">
                    <strong>Message:</strong>
                </div>
                
                <div class="message-content">
                    {{ $data['message'] }}
                </div>
            </div>
            
            <p>Please review and follow up with the user as soon as possible.</p>
            
            <p class="mt-20">
                Best regards,<br>
                The Reconxi Team
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="social-icons">
                <a href="https://www.instagram.com/reconxi02/">
                    <img src="{{ config('app.url') }}/assets/instagram-icon.svg" alt="Instagram">
                </a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361">
                    <img src="{{ config('app.url') }}/assets/facebook-icon.svg" alt="Facebook">
                </a>
                <a href="https://www.linkedin.com/in/recon-xi-b06835354">
                    <img src="{{ config('app.url') }}/assets/linkedin-icon.svg" alt="LinkedIn">
                </a>
                <a href="https://x.com/reconxi02">
                    <img src="{{ config('app.url') }}/assets/twitter-icon.svg" alt="Twitter">
                </a>
            </div>
            
            <p>Thank you for choosing "ReconXi". Need help? <a href="{{ Storage::url('contact') }}" class="footer-links">Contact us</a></p>
            
            <div class="divider"></div>
        </div>
    </div>
</body>
</html>