<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GrabAllPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = config('wordpress.url');
        $postName = config('wordpress.post_name');

        $page = 1;

        $pageExists = true;

        $posts = collect();

        while ($pageExists) {
            $response = \Http::get("{$url}/wp-json/wp/v2/posts?_embed&per_page=100&page={$page}");

            if ($response->successful()) {
                $posts = $posts->merge($response->json());
                $page++;
            } else {
                $pageExists = false;
            }
        }
    }
}
