<?php

namespace App\Enums;

enum Platform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';
    case TikTok = 'tiktok';
    case Snapchat = 'snapchat';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook Messenger',
            self::Instagram => 'Instagram',
            self::WhatsApp => 'WhatsApp',
            self::Telegram => 'Telegram',
            self::TikTok => 'TikTok',
            self::Snapchat => 'Snapchat',
            self::Email => 'Email',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Facebook => 'facebook',
            self::Instagram => 'instagram',
            self::WhatsApp => 'whatsapp',
            self::Telegram => 'telegram',
            self::TikTok => 'tiktok',
            self::Snapchat => 'snapchat',
            self::Email => 'envelope',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Facebook => '#1877F2',
            self::Instagram => '#E4405F',
            self::WhatsApp => '#25D366',
            self::Telegram => '#0088cc',
            self::TikTok => '#010101',
            self::Snapchat => '#FFFC00',
            self::Email => '#EA4335',
        };
    }
}
