<?php

namespace App\Search;

class SearchSuggestion
{
    public function __construct(
        public string $type,
        public string $title,
        public string $url,
        public ?string $subtitle = null,
    ) {}

    /**
     * @return array{type: string, title: string, subtitle?: string, url: string}
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
