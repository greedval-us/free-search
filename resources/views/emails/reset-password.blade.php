<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#020617;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;color:#e2e8f0;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $preheader }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:radial-gradient(circle at top, #0f172a 0%, #020617 46%, #020617 100%);margin:0;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;">
                    <tr>
                        <td style="padding:0 0 16px 0;text-align:center;">
                            <div style="display:inline-block;padding:8px 14px;border:1px solid rgba(125,211,252,0.28);border-radius:999px;background:rgba(14,165,233,0.08);color:#bae6fd;font-size:12px;font-weight:700;letter-spacing:0.24em;text-transform:uppercase;">
                                {{ $appName }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #1e293b;border-radius:28px;background:linear-gradient(180deg, rgba(15,23,42,0.96) 0%, rgba(2,6,23,0.98) 100%);box-shadow:0 28px 80px rgba(14,165,233,0.16);overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:40px 40px 16px 40px;">
                                        <div style="width:56px;height:56px;line-height:56px;text-align:center;border-radius:18px;background:linear-gradient(135deg, rgba(14,165,233,0.22), rgba(34,211,238,0.18));border:1px solid rgba(125,211,252,0.24);font-size:28px;">
                                            🔐
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 8px 40px;color:#f8fafc;font-size:32px;line-height:1.2;font-weight:700;">
                                        {{ $title }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 24px 40px;color:#cbd5e1;font-size:16px;line-height:1.75;">
                                        {{ $intro }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 28px 40px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="border-radius:16px;background:linear-gradient(135deg, #0ea5e9 0%, #22d3ee 100%);">
                                                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:16px 28px;color:#03131a;text-decoration:none;font-size:15px;font-weight:700;">
                                                        {{ $buttonText }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 18px 40px;">
                                        <div style="padding:18px 20px;border-radius:18px;border:1px solid rgba(51,65,85,0.9);background:rgba(15,23,42,0.72);color:#cbd5e1;font-size:14px;line-height:1.7;">
                                            {{ $expiry }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 12px 40px;color:#94a3b8;font-size:13px;line-height:1.7;">
                                        {{ $fallback }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 28px 40px;">
                                        <a href="{{ $actionUrl }}" style="color:#7dd3fc;font-size:13px;line-height:1.8;word-break:break-all;text-decoration:none;">
                                            {{ $actionUrl }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 12px 40px;color:#e2e8f0;font-size:14px;line-height:1.7;font-weight:600;">
                                        {{ $security }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 40px 40px 40px;color:#94a3b8;font-size:13px;line-height:1.7;">
                                        {{ $ignore }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 18px 0 18px;text-align:center;color:#64748b;font-size:12px;line-height:1.7;">
                            <div>{{ $signature }}</div>
                            <div style="margin-top:4px;">
                                <a href="{{ $appUrl }}" style="color:#7dd3fc;text-decoration:none;">{{ $appUrl }}</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
