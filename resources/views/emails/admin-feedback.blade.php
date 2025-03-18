<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer Feedback - ReconXi</title>
    <style>
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF !important;
            color: #333333 !important;
            line-height: 1.6;
        }
        
        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Header section */
        .header {
            background-color: #EAEFED !important;
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
            background-color: #FFFFFF !important;
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
            border: 1px solid #E0E0E0 !important;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
            background-color: #FAFAFA !important;
        }
        
        .message-details {
            margin-bottom: 10px;
        }
        
        .message-content {
            white-space: pre-line;
        }
        
        /* Footer section */
        .footer {
            background-color: #EAEFED !important;
            padding: 20px 0;
            text-align: center;
            width: 100%;
            font-size: 14px;
            color: #666666 !important;
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
            color: #2E604A !important;
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
            <img src="{{ $message->embed(public_path('assets/logo.png')) }}" alt="ReconXi Logo" class="logo">
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="illustration">
                <img src="{{ $message->embed(public_path('assets/message-received.png')) }}" alt="Email Notification">
            </div>
            
            <h2>Hello Admin</h2>
            
            <p>A new customer feedback request has been submitted. Please find the details below:</p>
            
            <div class="message-box">
                <div class="message-details">
                    <strong>Name:</strong> {{ $feedback->name }}
                </div>
                
                <div class="message-details">
                    <strong>Email:</strong> {{ $feedback->email }}
                </div>

                <div class="message-details">
                    <strong>Request Type:</strong> {{ $feedback->request_type }}
                </div>
                
                <div class="message-details">
                    <strong>Message:</strong>
                </div>
                
                <div class="message-content">
                    {{ $feedback->message }}
                </div>
            </div>
            
            <p>
                Please review the submission and assess any necessary follow-up actions. If the feedback 
                requires further clarification or a response, kindly reach out to the customer to acknowledge their 
                input or provide any relevant updates. Ensuring a prompt and thoughtful response will help 
                maintain a positive customer experience and demonstrate our commitment to continuous 
                improvement.
            </p>
            
            <p class="mt-20">
                Best regards,<br/>
                Sasa<br/>
                Customer Experience<br/>
                The ReconXi Team
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="social-icons">
                <a href="https://www.instagram.com/reconxi02">
                    <img src="{{ $message->embed(public_path('assets/instagram-icon.png')) }}" alt="Instagram">
                </a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361">
                    <img src="{{ $message->embed(public_path('assets/facebook-icon.png')) }}" alt="Facebook">
                </a>
                <a href="https://www.linkedin.com/in/recon-xi-b06835354">
                    <img src="{{ $message->embed(public_path('assets/linkedin-icon.png')) }}" alt="LinkedIn">
                </a>
                <a href="https://x.com/reconxi02">
                    <img src="{{ $message->embed(public_path('assets/twitter-icon.png')) }}" alt="Twitter">
                </a>
            </div>
        </div>
    </div>
</body>
</html>