<!DOCTYPE html>
<html>
<body style="font-family: Verdana, sans-serif; color: #333; margin: 0; padding: 0;">
    <div style="text-align: center; padding: 20px;">
        <h2>SyneriaBooks Invitation</h2>
        
        <p>Hello!</p>
        <p>You have been invited by <strong>{{ $inviterName }}</strong> to join <strong>{{ $companyName }}</strong>.</p>
        
        <div style="margin: 30px 0;">
            <!-- Button styled to match the visual weight of the OTP code block -->
            <a href="{{ $loginUrl }}" style="background: #0d6efd; color: #ffffff; text-decoration: none; display: inline-block; padding: 15px 30px; border-radius: 5px; font-weight: bold;">Log in to SyneriaBooks</a>
        </div>

        <p>You will use your email address (<strong>{{ $user->email }}</strong>) to sign in.</p>

        <div style="margin-top: 20px; font-size: 12px; color: #777; line-height: 1.5;">
            <p>If you did not expect this invitation, you can ignore this email.</p>
            <p>Thanks,<br>The SyneriaBooks Team</p>
        </div>
    </div>
</body>
</html>