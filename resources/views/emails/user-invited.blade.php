<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're invited to Time Tracking</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#6366f1,#9333ea);padding:28px 32px;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;">Time Tracking</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;color:#374151;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">Hi {{ $name }},</p>
                            <p style="margin:0 0 16px;">
                                {{ $invitedBy }} has created an account for you on Time Tracking.
                                Use the credentials below to sign in, then change your password from your profile.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;margin:0 0 20px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <div style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;">Email</div>
                                        <div style="font-size:15px;color:#111827;font-weight:600;margin-bottom:12px;">{{ $email }}</div>
                                        <div style="font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;">Temporary password</div>
                                        <div style="font-size:15px;color:#111827;font-weight:600;font-family:monospace;">{{ $temporaryPassword }}</div>
                                    </td>
                                </tr>
                            </table>

                            <a href="{{ $loginUrl }}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#9333ea);color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 24px;border-radius:12px;">
                                Sign in
                            </a>

                            <p style="margin:20px 0 0;font-size:13px;color:#9ca3af;">
                                If you weren't expecting this invitation, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
