<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all scheduled posts whose publish time has arrived';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $posts = Post::where('status', 'scheduled')
                    ->where('published_at', '<=', now())
                    ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts to publish.');
            return;
        }

        foreach ($posts as $post) {
            $post->update(['status' => 'published']);
            $this->info("Published: [{$post->id}] $post->title");
        }

        $this->info("Total published: {$posts->count()} post(s).");
    }
}
