<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed from ReconXi</title>
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
            color: #2c664f !important;
            text-decoration: underline;
            cursor: pointer;
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
            <img src="{{ $base_url }}/assets/thank-you-illustration.png" alt="Thank You" width="200">
            <h2>You've Successfully Unsubscribed<br>from ReconXI</h2>
            
            <div class="text-content">
                <p>Dear Subscriber,</p>
                
                <p>We're writing to confirm that you've been successfully unsubscribed from ReconXI's email communications. You will no longer receive updates or newsletters from us at {{ $email }}.</p>
                
                <p>We're sorry to see you go and would appreciate any feedback you might have to help us improve. If you have a moment, please let us know why you chose to unsubscribe</p>
                
                <p><a href="mailto:support@reconxi.com" class="button">Contact us</a></p>
                
                <p>If you change your mind, you're always welcome to <a href="#" class="button resubscribe-btn" data-email="{{ $email }}">resubscribe here</a>.</p>
                
                <p>Thank you for your past engagement with ReconXI.</p>
                
                <p>Best regards,<br>The Reconxi Team</p>
            </div>
        </div>
        
        <div class="footer">
            <div class="social-links">
                <a href="https://instagram.com/reconxi"><img src="{{ $base_url }}/assets/instagram-icon.png" alt="Instagram" width="24"></a>
                <a href="https://facebook.com/reconxi"><img src="{{ $base_url }}/assets/facebook-icon.png" alt="Facebook" width="24"></a>
                <a href="https://linkedin.com/company/reconxi"><img src="{{ $base_url }}/assets/linkedin-icon.png" alt="LinkedIn" width="24"></a>
                <a href="https://twitter.com/reconxi"><img src="{{ $base_url }}/assets/twitter-icon.png" alt="Twitter" width="24"></a>
            </div>
            
            <p>Thank you for choosing "ReconXi". Need help? <a href="mailto:support@reconxi.com">Contact us</a></p>
            
            <div class="divider"></div>
            
            <p>You received this email because you unsubscribed from Reconxi.com. Want to update your subscription preferences?</p>
            <p>You can <a href="#" class="button resubscribe-btn" data-email="{{ $email }}">resubscribe</a> to continue receiving our updates.</p>
        </div>
    </div>

    <!-- Notification Message -->
    <div class="notification" id="notification">Resubscription successful! 🎉</div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".resubscribe-btn").forEach(button => {
                button.addEventListener("click", function (event) {
                    event.preventDefault(); // Prevent default link behavior
                    event.stopPropagation(); // Stop event from bubbling up

                    let email = this.getAttribute("data-email");
                    const url = "{{ $base_url }}/api/v1/newsletter/subscribe";

                    // Perform AJAX request (Mock example)
                    fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // showNotification("Resubscription successful! 🎉");
                        if (data.success) {
                            showNotification(data.message);
                        } else {
                            showNotification("An error occurred: " + data.message, "red");
                        }
                    })
                    .catch(error => {
                        showNotification("Something went wrong. Please try again!", "red");
                    });
                });
            });

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