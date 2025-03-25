<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #fcfcfc !important;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #eaefed !important;
            padding: 20px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin: 0 auto;
        }
        .thank-you {
            text-align: center;
            margin: 30px 0;
        }
        .thank-you img {
            max-width: 150px;
        }
        .content {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .footer {
            background-color: #eaefed !important;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666 !important;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
        }
        .social-links img {
            width: 24px;
            height: 24px;
        }
        .divider {
            border-top: 1px dashed #ccc;
            margin: 20px 0;
        }
        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .details {
            margin: 20px 0;
        }
        .details p {
            margin: 10px 0;
        }
        .details strong {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $message->embed(public_path('assets/logo.png')) }}" alt="ReconXi" class="logo">
    </div>
    
    <div class="content">
        <div class="thank-you">
            <img src="{{ $message->embed(public_path('assets/thank-you.png')) }}" alt="Thank You">
        </div>
        
        <h1>Confirmation of Your Feedback Submission</h1>
        
        <p>Dear {{ $feedback->name }},</p>
        
        <p>Thank you for reaching out to us!</p>
        <p>We have received your feedback request and appreciate you taking the time to share your thoughts. Your input is valuable to us and helps us improve our product.</p>
        
        <div class="details">
            <p><strong>Submission Details</strong></p>
            <p><strong>Date:</strong> {{ $feedback->created_at->format('d/m/Y') }}.</p>
            <p><strong>Request Type:</strong> {{ $feedback->request_type ?? "Feedback" }}.</p>
            <p><strong>Subject:</strong> {{ $feedback->subject ?? "Subject" }}.</p>
            <p><strong>Message:</strong> {{ $feedback->message }}</p>
        </div>
        
        <p>Our team will review your submission and get back to you as soon as possible. If you have any urgent questions, feel free to reply to this email.</p>
        
        <p>Best regards,</p>
        <p>The ReconXi Team</p>
    </div>
    
    <div class="footer">
        <div class="social-links">
            <a href="https://www.instagram.com/reconxihq?igsh=MXd5a3Q2YmZrb2s5bg%3D%3D&utm_source=qr"><img src="{{ $message->embed(public_path('assets/instagram-icon.png')) }}" alt="Instagram"></a>
            <a href="https://www.facebook.com/profile.php?id=61573471907361"><img src="{{ $message->embed(public_path('assets/facebook-icon.png')) }}" alt="Facebook"></a>
            <a href="http://www.linkedin.com/in/recon-xi-b06835354"><img src="{{ $message->embed(public_path('assets/linkedin-icon.png')) }}" alt="LinkedIn"></a>
            <a href="https://x.com/thereconxi"><img src="{{ $message->embed(public_path('assets/twitter-icon.png')) }}" alt="Twitter"></a>
        </div>
        
        <p>Thank you for choosing ReconXi. Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
        
        <div class="divider"></div>
        
        <p>You are receiving this email because you signed up at <a href="https://reconxi.com/">ReconXi.com</a>. Want to change how you receive these emails?</p>
        <p>You can <a href="{{ route('newsletter.one-click-unsubscribe', ['email' => $feedback->email]) }}">unsubscribe</a> from this list.</p>
    </div>
</body>
</html>