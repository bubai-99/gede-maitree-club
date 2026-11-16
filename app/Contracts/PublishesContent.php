<?php

namespace App\Contracts;

/**
 * Website content with a draft and a published state (see App\Models\Concerns\Publishable).
 */
interface PublishesContent
{
    public function isPublished(): bool;

    public function publish(): void;

    public function unpublish(): void;
}
