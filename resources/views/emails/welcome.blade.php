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
        .flash-message {
            display: none; 
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #2c664f; /* Success Color */
            color: #fff !important;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .flash-message.show {
            display: block;
            opacity: 1;
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
        <div class="header">
            <img src="{{ $base_url }}/assets/logo.png" alt="ReconXi Logo" height="40">
        </div>
        
        <div class="content">
            <img src="{{ $base_url }}/assets/welcome-illustration.png" alt="Welcome" width="200">
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
            
            <p>Best regards,<br>The Reconxi Team</p>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="https://www.instagram.com/reconxi02"><img src="{{ $base_url }}/assets/instagram-icon.png" alt="Instagram" width="24"></a>
                <a href="https://www.facebook.com/profile.php?id=61573471907361"><img src="{{ $base_url }}/assets/facebook-icon.png" alt="Facebook" width="24"></a>
                <a href="https://www.linkedin.com/in/recon-xi-b06835354"><img src="{{ $base_url }}/assets/linkedin-icon.png" alt="LinkedIn" width="24"></a>
                <a href="https://x.com/reconxi02"><img src="{{ $base_url }}/assets/twitter-icon.png" alt="Twitter" width="24"></a>
            </div>
            
            <p>Thank you for choosing "ReconXi". Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
            
            <!-- <div id="flash-message" class="flash-message"></div> -->
            <div class="divider"></div>
            
            <p>You are receiving this email because you signed up at Reconxi.com. Want to change how you receive these emails?</p>
            <p>You can <a href="#" id="unsubscribe-btn" data-email="{{ $user->email }}">unsubscribe</a> from this list.</p>
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
                        /* if (data.success) {
                            showFlashMessage(data.message, "success");
                        } else {
                            showFlashMessage("An error occurred: " + data.message, "error");
                        } */
                        if (data.success) {
                            showNotification(data.message);
                        } else {
                            showNotification("An error occurred: " + data.message, "red");
                        }
                    })
                    .catch(error => {
                        // showFlashMessage("An error occurred. Please try again.", "error");
                        showNotification("Something went wrong. Please try again!", "red");
                        console.error(error);
                    });
                });
            }

            function showFlashMessage(message, type = "success") {
                const flashMessage = document.getElementById("flash-message");

                if (type === "error") {
                    flashMessage.style.backgroundColor = "#d9534f"; // Red for error
                } else {
                    flashMessage.style.backgroundColor = "#2c664f"; // Green for success
                }

                flashMessage.innerText = message;
                flashMessage.classList.add("show");

                setTimeout(() => {
                    flashMessage.classList.remove("show");
                    setTimeout(() => flashMessage.style.display = "none", 500); // Wait for transition
                }, 5000); // Hide after 5 seconds
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