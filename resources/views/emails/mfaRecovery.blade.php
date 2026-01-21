<!DOCTYPE html>
<html>
<body style="font-family: Verdana, sans-serif; color: #333; margin: 0; padding: 0;">
    <div style="text-align: center; padding: 20px;">
        <h2>SyneriaBooks Security</h2>
        
        <p>Hello {{ $user->first_name }}!</p>
        <p>Two-Factor Authentication (MFA) has been enabled on your account.</p>
        
        <p><strong>Please save your recovery codes safely.</strong><br>
        If you lose access to your device, these are the only way to recover your account.</p>
        
        <div style="margin: 30px 0;">
            <!-- Recovery Codes Block -->
            <div style="background: #f3f4f6; color: #1f2937; display: inline-block; padding: 20px 40px; border-radius: 5px; border: 1px solid #e5e7eb;">
                <div style="font-family: monospace; font-size: 16px; letter-spacing: 2px; text-align: left;">
                    @foreach($recoveryCodes as $code)
                        <div style="margin: 8px 0; font-weight: bold;">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <p>You can use each code only once.</p>

        <div style="margin-top: 20px; font-size: 12px; color: #777; line-height: 1.5;">
            <p>If you did not enable MFA yourself, please contact support immediately.</p>
            <p>Thanks,<br>The SyneriaBooks Team</p>
        </div>
    </div>
</body>
</html>