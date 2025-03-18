<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <title>ReconXI Header</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            max-width: 790px;
            margin: 0 auto;
            width: 100%;
            font-family: "Inter", sans-serif;
        }

        .header-container {
            background: #EAEFED;
            width: 100%;
            padding: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .subject {
            font-weight: 600;
            color: #333333;
            text-align: center
        }

        .subject-container {
            padding: 40px;
        }

        .subject-container h1 {
            font-size: 16px
        }

        .hero {
            max-width: 395px;
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
        }

        .hero img {
            width: 100%
        }

        .content {
            padding: 40px 56px;
        }

        .content h6 {
            font-size: 18px;
            color: #333333;
            font-weight: 600;
        }

        .content p {
            font-size: 14px;
            color: #333333;
            margin: 15px 0;
            line-height: 1.5;
        }

        /* Footer section */
        .footer {
            background-color: #EAEFED !important;
            padding: 32px 48px;
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

        .footer-bottom {
            text-align: left
        }

        .p {
            padding: 10px 0
        }

        .p span {
            font-weight: 600;
            color: #111111
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

        }
    </style>
</head>

<body>
    <header class="header-container">
        <div class="logo-container">
            <img src="{{ $message->embed(public_path('assets/logo.png')) }}" alt="">
        </div>
    </header>
    <div class="subject-container">
        <h1 class="subject"> {{ $data->full_name }}, YOU’RE JUST A CLICK AWAY FROM SAVING MONEY AND TIME.</h1>
    </div>
    <div class=" hero">
        <img src="{{ $message->embed(public_path('assets/outbound-hero.png')) }}" alt="">
    </div>
    <div class=" content">
        <h6>Hi {{ $data->full_name }},</h6>
        <p>
            Thank you for your interest in ReconXi! We noticed you recently engaged with our ad and filled out your
            details—great choice!
        </p>
        <p>

            You’re this close to transforming how you reconcile financial records—but time is ticking.
            Every day without ReconXi means more manual work, more errors, and more wasted hours.
        </p>
        <p>
            Why struggle when you can have AI-powered accuracy and seamless reconciliation in minutes?
        </p>
        <p>
            Watch the Demo Now!
        </p>
        <p>
            ➡️
            <a href="https://drive.google.com/file/d/1SaIAF_aF483lICD6dAFEumhWhnjpkcry/view"> Click here!
            </a>
        </p>
        <p>

            You’ll love how easy it is to manage your schedule once you’ve perform your reconciliations.
        </p>
        <p>
            Don’t just take our word for it. Experience ReconXi yourself.
        </p>
        <p>
            See you inside,
        </p>
        <p>
            The ReconXi Team.
        </p>
    </div>
    <footer>
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

            <p>Thank you for choosing ReconXi. Need help? <a href="mailto:support@reconxi.com" class="footer-links">Contact us</a></p>

            <div class="divider"></div>

            <div class=" footer-bottom">
                <p>You are receiving this email because you signed up at <a href="https://reconxi.com/">ReconXi.com</a>. Want to change how you receive
                    these
                    emails?</p>

                {{-- <p>You can <a id="unsubscribe-btn" href="#" class="footer-links">unsubscribe</a> from this list.</p> --}}
                <p class=" p">You can <span> <a
                            href="{{ url('api/v1/newsletter/unsubscribe/' . $email) }}">unsubscribe</a> from this
                        list.</span></p>
            </div>
        </div>
    </footer>
</body>

</html>
