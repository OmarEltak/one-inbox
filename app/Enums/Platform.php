<?php

namespace App\Enums;

enum Platform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook Messenger',
            self::Instagram => 'Instagram',
            self::WhatsApp => 'WhatsApp',
            self::Telegram => 'Telegram',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Facebook => 'facebook',
            self::Instagram => 'instagram',
            self::WhatsApp => 'whatsapp',
            self::Telegram => 'telegram',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Facebook => '#1877F2',
            self::Instagram => '#E4405F',
            self::WhatsApp => '#25D366',
            self::Telegram => '#0088cc',
        };
    }
}
