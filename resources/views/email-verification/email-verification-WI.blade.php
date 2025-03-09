<!-- (WI- with image) email-verfication  -->
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

        .icon-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .icon {
            height: 60px;
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
            background-color: #2E604A;
            color: white;
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
        <img src="./logo.svg" alt="ReconXi Logo" class="logo">
    </div>

    <div class="main-content">
        <div class="icon-container">
            <img src="./icon.svg" alt="Verification Icon" class="icon">
        </div>

        <h1 class="page-title">Email Verification</h1>

        <div class="content-container">
            <h2 class="greeting">Hi {{ $user->name }},</h2>
            <p class="message">Thanks for registering your account with us ReconXi. Before we get started, we just need to confirm that this is you.</p>
            <p class="expiry-notice">This link will expire 30 minutes after this email has been sent. If you did not make this request, you can ignore this email</p>
            <p class="instruction">To verify your email, please click the button below:</p>
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button">Verify Account</a>
            </div>

            <p class="link-text">Or copy this link:</p>
            <a href="{{ $verificationUrl }}" class="verification-link">{{ $verificationUrl }}</a>

            <div class="signature">
                <p>Regards,</p>
                <p>ReconXi</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="social-icons">
            <a href="https://www.instagram.com/reconxi02/?igsh=YTh5aWx6Y2c2dW0w#">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>
            <a href="https://www.facebook.com/profile.php?id=61573471907361&mibextid=rS40aB7S9Ucbxw6v">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                </svg>
            </a>
            <a href="https://www.linkedin.com/in/recon-xi-b06835354">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path d="M4.98 3.5c0 1.381-1.11 2.5-2.48 2.5s-2.48-1.119-2.48-2.5c0-1.38 1.11-2.5 2.48-2.5s2.48 1.12 2.48 2.5zm.02 4.5h-5v16h5v-16zm7.982 0h-4.968v16h4.969v-8.399c0-4.67 6.029-5.052 6.029 0v8.399h4.988v-10.131c0-7.88-8.922-7.593-11.018-3.714v-2.155z"/>
                </svg>
            </a>
            <a href="https://x.com/reconxi02?s=21&t=6GEcIpxFOrczvmtrZsCzSw">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
            </a>
        </div>

        <p class="footer-text">Thank you for choosing "ReconXi". Need help? <a href="#contact" class="footer-link">Contact us</a></p>

        <div class="divider"></div>

        <p class="footer-message">You are receiving this email because you signed up at Reconxi.com. Want to change how you receive these emails?</p>

        <p class="preference-links">
            You can <a href="#preferences" class="preference-link">update your preferences</a> or <a href="#unsubscribe" class="preference-link">unsubscribe from this list</a>.
        </p>
    </div>
</body>
</html>

