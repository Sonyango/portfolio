<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl = 'https://api.brevo.com/v3';

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->apiKey   = config('services.brevo.api_key');
        $this->fromEmail = config('services.brevo.from_email');
        $this->fromName = config('services.brevo.from_name');
    }

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent
    ): bool {
        try {
            $response = Http::withHeaders([
                'api-key'   => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'    => 'application/json',
            ])->post("{$this->baseUrl}/smtp/email", [
                'sender'    => [
                    'email' => $this->fromEmail,
                    'name'  => $this->fromName,
                ],
                'to'    => [
                    ['email' => $toEmail, 'name' => $toName]
                ],
                'subject'   => $subject,
                'htmlContent' => $htmlContent,
            ]);

            if (!$response->successful()) {
                Log::error('Brevo email failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch(\Exception $e) {
            Log::error('Brevo send exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    // Email templates
    public function sendMfaCode(
        string $email,
        string $name,
        string $code
    ): bool {
        return $this->send(
            $email,
            $name,
            'Your Login Verificaton Code',
            $this->mfaCodeTemplate($name, $code)
        );
    }

    public function sendLoginAlert(
        string $email,
        string $name,
        string $ip,
        string $userAgent,
        string $time
    ): bool {
        return $this->send(
            $email,
            $name,
            'New Login Detected - Portfolio Admin',
            $this->loginAlertTemplate($name, $ip, $userAgent, $time)
        );
    }

    public function sendSuspiciousLoginAlert(
        string $email,
        string $name,
        string $ip,
        int $failedAttempts
    ): bool {
        return $this->send(
            $email,
            $name,
            '!! Suspecious Login Activity Detected!!',
            $this->suspiciousLoginTemplate($name, $ip, $failedAttempts)
        );
    }

    public function sendAccountLockout(
        string $email,
        string $name,
        string $ip,
        int $minutesLocked
    ): bool {
        return $this->send(
            $email,
            $name,
            'Your Admin Account Has Been Temporarily Locked',
            $this->lockoutTemplate($name, $ip, $minutesLocked)
        );
    }

    public function sendPasswordReset(
        string $email,
        string $name,
        string $resetUrl
    ): bool {
        return $this->send(
            $email,
            $name,
            'Reset Your Admin password',
            $this->passwordResetTemplate($name, $resetUrl)
        );
    }

    public function sendPasswordChanged(
        string $email,
        string $name,
        string $ip
    ): bool {
        return $this->send(
            $email,
            $name,
            'Your Password Has Been Changed',
            $this->passwordChangedTemplate($name, $ip)
        );
    }

    // HTML Templates
    private function baseTemplate(string $content): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='utf-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1'>
          <style>
            body { font-family: Arial, sans-serif; background: #0f172a;
                   color: #e2e8f0; margin: 0; padding: 20px; }
            .container { max-width: 560px; margin: 0 auto;
                         background: #1e293b; border-radius: 12px;
                         border: 1px solid #334155; overflow: hidden; }
            .header { background: #4f46e5; padding: 24px;
                      text-align: center; }
            .header h1 { color: #fff; margin: 0; font-size: 20px; }
            .body { padding: 32px 24px; }
            .code { background: #0f172a; border: 2px dashed #4f46e5;
                    border-radius: 8px; padding: 16px; text-align: center;
                    font-size: 32px; font-weight: bold; letter-spacing: 8px;
                    color: #818cf8; margin: 20px 0; }
            .btn { display: inline-block; background: #4f46e5;
                   color: #fff !important; text-decoration: none;
                   padding: 12px 24px; border-radius: 8px;
                   font-weight: bold; margin: 16px 0; }
            .alert { background: #7f1d1d; border: 1px solid #ef4444;
                     border-radius: 8px; padding: 16px; margin: 16px 0; }
            .info { background: #0c4a6e; border: 1px solid #0ea5e9;
                    border-radius: 8px; padding: 16px; margin: 16px 0; }
            .footer { padding: 16px 24px; border-top: 1px solid #334155;
                      font-size: 12px; color: #64748b; text-align: center; }
            p { line-height: 1.6; color: #cbd5e1; }
            small { color: #64748b; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class='container'>
            <div class='header'>
              <h1>Portfolio Admin</h1>
            </div>
            <div class='body'>{$content}</div>
            <div class='footer'>
              This is an automated security notification. Do not reply.
            </div>
          </div>
        </body>
        </html>";
    }

    private function mfaCodeTemplate(
        string $name,
        string $code
    ): string {
        return $this->baseTemplate("
        <p>Hello <strong>{$name}</strong>,</p>
            <p>Your verification code for Portfolio Admin login is:</p>
            <div class='code'>{$code}</div>
            <p>This code expires in <strong>10 minutes</strong>.</p>
            <div class='alert'>
              <strong> Never share this code.</strong>
              If you did not request this, your account may be at risk.
              Change your password immediately.
            </div>
        ");
    }

    private function loginAlertTemplate(
        string $name,
        string $ip,
        string $userAgent,
        string $time
    ): string {
        return $this->baseTemplate("
            <p>Hello <strong>{$name}</strong>,</p>
            <p>A successful login was recorded on your Portfolio Admin account.</p>
            <div class='info'>
              <strong>Login Details</strong><br>
              🕐 Time: {$time}<br>
              🌐 IP Address: {$ip}<br>
              💻 Device: {$userAgent}
            </div>
            <p>If this was you, no action is needed.</p>
            <div class='alert'>
              If this was <strong>NOT you</strong>, reset your password immediately
              and contact your hosting provider.
            </div>
        ");
    }

    private function suspiciousLoginTemplate(
        string $name,
        string $ip,
        int $attempts
    ): string {
        return $this->baseTemplate("
            <p>Hello <strong>{$name}</strong>,</p>
            <div class='alert'>
              <strong>!! Suspicious Activity Detected</strong><br>
              There have been <strong>{$attempts} failed login attempts</strong>
              on your Portfolio Admin account from IP <strong>{$ip}</strong>.
            </div>
            <p>If this was not you, your account may be under a brute-force attack.</p>
            <p><strong>Recommended actions:</strong></p>
            <ul>
              <li>Change your password immediately</li>
              <li>Enable MFA if not already enabled</li>
              <li>Check your server firewall and block the IP if needed</li>
            </ul>
        ");
    }

    private function lockoutTemplate(
        string $name,
        string $ip,
        int $minutes
    ): string {
        return $this->baseTemplate("
        <p>Hello <strong>{$name}</strong>,</p>
            <div class='alert'>
              <strong>Account Temporarily Locked</strong><br>
              Your account has been locked for <strong>{$minutes} minutes</strong>
              due to multiple failed login attempts from IP <strong>{$ip}</strong>.
            </div>
            <p>Your account will unlock automatically after the lockout period.</p>
            <p>If you forgot your password, use the password reset option on the login page.</p>
        ");
    }

    private function passwordResetTemplate(
        string $name,
        string $resetUrl
    ): string {
        return $this->baseTemplate("
        <p>Hello <strong>{$name}</strong>,</p>
            <p>A password reset was requested for your Portfolio Admin account.</p>
            <p style='text-align:center;'>
              <a href='{$resetUrl}' class='btn'>Reset My Password</a>
            </p>
            <p>This link expires in <strong>30 minutes</strong>.</p>
            <div class='alert'>
              If you did not request a password reset, ignore this email.
              Your password will not change.
            </div>
            <small>If the button doesn't work, copy and paste this URL:<br>
            {$resetUrl}</small>
        ");
    }

    private function passwordChangedTemplate(
        string $name,
        string $ip
    ): string {
        return $this->baseTemplate("
        <p>Hello <strong>{$name}</strong>,</p>
            <p>Your Portfolio Admin password was successfully changed.</p>
            <div class='info'>
              🌐 Changed from IP: {$ip}<br>
              🕐 Time: " . now()->format('d M Y H:i:s T') . "
            </div>
            <div class='alert'>
              If you did not make this change, reset your password immediately
              and contact your hosting provider.
            </div>
        ");
    }
}
