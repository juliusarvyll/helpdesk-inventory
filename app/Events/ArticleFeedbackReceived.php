<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\KbArticle;
use App\Models\KbArticleFeedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleFeedbackReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly KbArticle $article,
        public readonly KbArticleFeedback $feedback,
    ) {}
}
