<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Our Platform</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">Welcome, {{ $user->name }}!</h2>
        <p style="font-size: 16px; color: #555;">
            Thank you for signing up with Google. We're excited to have you on board!
        </p>
        <p style="font-size: 16px; color: #555;">
            You can start using our services right away.
        </p>
        <a href="{{ env('FRONTEND_URL', 'https://reconxi.com/') }}" 
           style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
           Get Started
        </a>
        <p style="margin-top: 20px; font-size: 14px; color: #777;">Best Regards,<br> The Team</p>
    </div>
</body>
</html>
