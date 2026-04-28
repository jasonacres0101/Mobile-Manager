<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class UserWelcomeEmailService
{
    public const DEFAULT_SUBJECT = 'Welcome to {app_name}';

    public const DEFAULT_BODY = <<<'HTML'
<h1>Welcome to {app_name}</h1>

<p>Hello {name},</p>

<p>Your customer portal account has been created. Use the button below to create your password and sign in.</p>

<p><a href="{password_setup_url}" style="background: #4f46e5; color: #ffffff; display: inline-block; padding: 12px 18px; text-decoration: none; border-radius: 6px;">Create password</a></p>

<p>If the button does not work, open this link:</p>
<p><a href="{password_setup_url}">{password_setup_url}</a></p>

<p>Thanks,<br>{app_name}</p>
HTML;

    public function __construct(private MicrosoftGraphMailService $mail, private AppSettings $settings) {}

    public function send(User $user): void
    {
        $this->sendUsingTemplate(
            $user,
            $this->settings->get('welcome_email.subject', self::DEFAULT_SUBJECT),
            $this->settings->get('welcome_email.body', self::DEFAULT_BODY),
        );
    }

    public function sendUsingTemplate(User $user, string $subjectTemplate, string $bodyTemplate): void
    {
        $token = Password::broker()->createToken($user);
        $url = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        $subject = $this->renderTemplate(
            $subjectTemplate,
            $user,
            $url,
        );
        $body = $this->renderTemplate(
            $bodyTemplate,
            $user,
            $url,
        );
        $html = view('emails.user-welcome', [
            'body' => $body,
        ])->render();

        $this->mail->sendHtml($user->email, $subject, $html);
    }

    private function renderTemplate(string $template, User $user, string $url): string
    {
        return strtr($template, [
            '{name}' => $user->name,
            '{email}' => $user->email,
            '{company}' => $user->company?->name ?? '',
            '{app_name}' => config('app.name', 'Mobile Manager'),
            '{password_setup_url}' => $url,
        ]);
    }
}
