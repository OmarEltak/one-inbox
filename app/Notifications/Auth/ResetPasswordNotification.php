<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60
        );

        return (new MailMessage)
            ->subject('Reset your OT1-Pro password')
            ->greeting('Hey there,')
            ->line("Someone (hopefully you) asked to reset the password on your OT1-Pro account.")
            ->line("Click the button below to set a new one. The link expires in {$expireMinutes} minutes.")
            ->action('Reset password', $url)
            ->line("If you didn't request this, you can safely ignore the email — nothing will change and your account stays secure.")
            ->salutation(new HtmlString(
                "— Omar<br>"
                ."Founder & sole developer of OT1-Pro<br><br>"
                ."P.S. This email lands from my personal Gmail because OT1-Pro is still a one-person team. "
                ."If anything is broken, confusing, or you need a feature we don't have yet — just hit reply. "
                ."I read every message and ship fixes fast."
            ));
    }
}
