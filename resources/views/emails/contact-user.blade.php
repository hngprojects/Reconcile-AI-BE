<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Confirmation - ReconXi</title>
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
            border: 1px solid #E0E0E0;
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
    @php $base_url = config('app.url'); @endphp
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ $base_url }}/assets/logo.png" alt="ReconXi Logo" class="logo">
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="illustration">
                <img src="{{ $base_url }}/assets/personal-data.png" alt="Email Notification">
            </div>
            
            <h2>Hi {{ $data['name'] }}</h2>
            
            <p>Thank you for reaching out to us at ReconXi. We have received your enquiry and our team is reviewing your message.</p>
            
            <p>We'll get back to you as soon as possible.</p>
            
            <p>Below is a copy of your message for your records:</p>
            
            <div class="message-box">
                
                <div class="message-details">
                    <strong>Message:</strong>
                </div>
                
                <div class="message-content">
                    {{ $data['message'] }}
                </div>
            </div>
            
            <p>Thank you for choosing ReconXi</p>
            
            <p class="mt-20">
                Best regards,<br>
                The Reconxi Team
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="social-icons">
                <a href="https://www.instagram.com/reconxi02/">
                    <img src="{{ $base_url }}/assets/instagram-icon.png" alt="Instagram">
                </a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361">
                    <img src="{{ $base_url }}/assets/facebook-icon.png" alt="Facebook">
                </a>
                <a href="https://www.linkedin.com/in/recon-xi-b06835354">
                    <img src="{{ $base_url }}/assets/linkedin-icon.png" alt="LinkedIn">
                </a>
                <a href="https://x.com/reconxi02">
                    <img src="{{ $base_url }}/assets/twitter-icon.png" alt="Twitter">
                </a>
            </div>
            
            <p>Thank you for choosing "ReconXi". Need help? <a href="mailto:support@reconxi.com" class="footer-links">Contact us</a></p>
            
            <div class="divider"></div>

            <p>You are receiving this email because you signed up at Reconxi.com. Want to change how you receive these emails?</p>
            
            <p>You can <a href="#" id="unsubscribe-btn" class="footer-links" data-email="{{ $data['email'] }}">unsubscribe</a> from this list.</p>
        </div>
    </div>

    <!-- Notification Message -->
    <div class="notification" id="notification">Resubscription successful! 🎉</div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const unsubscribeBtn = document.getElementById("unsubscribe-btn");

            if (unsubscribeBtn) {
                unsubscribeBtn.addEventListener("click", function (event) {
                    event.preventDefault(); // Prevent page navigation

                    const email = this.getAttribute("data-email");
                    const url = "{{ $base_url }}/api/v1/newsletter/unsubscribe";

                    fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message);
                        } else {
                            showNotification("An error occurred: " + data.message, "red");
                        }
                    })
                    .catch(error => {
                        showNotification("Something went wrong. Please try again!", "red");
                        console.error(error);
                    });
                });
            }

            function showNotification(message, bgColor = "#2c664f") {
                let notification = document.getElementById("notification");
                notification.textContent = message;
                notification.style.backgroundColor = bgColor;
                notification.style.display = "block";

                setTimeout(() => {
                    notification.style.display = "none";
                }, 7000); // Hide after 7 seconds
            }
        });
    </script>
</body>
</html>