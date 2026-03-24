<?php

namespace App\Livewire\Content;

use Livewire\Component;

class Index extends Component
{
    public string $tab = 'all';

    public array $content = [
        [
            'title' => 'Summer Sale Announcement',
            'platform' => 'instagram',
            'status' => 'published',
            'date' => '2026-03-15',
            'views' => 12400,
            'reach' => 8300,
            'engagement' => 4.2,
            'clicks' => 320,
        ],
        [
            'title' => 'Product Launch Video',
            'platform' => 'tiktok',
            'status' => 'published',
            'date' => '2026-03-14',
            'views' => 45200,
            'reach' => 32100,
            'engagement' => 7.8,
            'clicks' => 1240,
        ],
        [
            'title' => 'Weekly Tips Newsletter',
            'platform' => 'email',
            'status' => 'scheduled',
            'date' => '2026-03-20',
            'views' => 0,
            'reach' => 0,
            'engagement' => 0,
            'clicks' => 0,
        ],
        [
            'title' => 'Customer Success Story',
            'platform' => 'facebook',
            'status' => 'draft',
            'date' => '2026-03-18',
            'views' => 0,
            'reach' => 0,
            'engagement' => 0,
            'clicks' => 0,
        ],
        [
            'title' => 'Behind the Scenes Reel',
            'platform' => 'instagram',
            'status' => 'published',
            'date' => '2026-03-12',
            'views' => 9800,
            'reach' => 7200,
            'engagement' => 5.1,
            'clicks' => 210,
        ],
        [
            'title' => 'Promo Code Blast',
            'platform' => 'whatsapp',
            'status' => 'published',
            'date' => '2026-03-10',
            'views' => 3200,
            'reach' => 3200,
            'engagement' => 12.4,
            'clicks' => 890,
        ],
        [
            'title' => 'Q2 Strategy Post',
            'platform' => 'facebook',
            'status' => 'scheduled',
            'date' => '2026-03-25',
            'views' => 0,
            'reach' => 0,
            'engagement' => 0,
            'clicks' => 0,
        ],
    ];

    public function filteredContent(): array
    {
        if ($this->tab === 'all') {
            return $this->content;
        }

        return array_values(array_filter($this->content, fn ($c) => $c['status'] === $this->tab));
    }

    public function render()
    {
        return view('livewire.content.index', [
            'items' => $this->filteredContent(),
        ])->layout('layouts.app', ['title' => 'Content']);
    }
}
