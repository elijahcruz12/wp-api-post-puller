<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\HTMLToMarkdown\HtmlConverter;

class PullPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:pull-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = config('wordpress.url');
        $postName = config('wordpress.post_name');

        $this->info('Pulling posts from ' . $url . '...');

        $page = 1;

        $pageExists = true;

        $posts = collect();

        while ($pageExists) {
            $response = \Http::get("{$url}/wp-json/wp/v2/posts?_embed&per_page=100&page={$page}");

            if ($response->successful()) {
                // Count of posts on page
                $count = count($response->json());

                $this->info('Page ' . $page . ' has ' . $count . ' posts.');

                $posts = $posts->merge($response->json());
                $page++;
            } else {
                $pageExists = false;
            }
        }

        $this->info('Found ' . $posts->count() . ' posts.');

        $this->info('Saving posts to database...');

        foreach ($posts as $post) {
            $postModel = \App\Models\Post::firstOrNew([
                'wordpress_id' => $post['id']
            ]);

            $postModel->title = $post['title']['rendered'];

            $content = $post['content']['rendered'];
            $excerpt = $post['excerpt']['rendered'];

            // If excerpt contains '<span class="excerpt-hellip"> \[…\]</span>', replace it with '...'
            $excerpt = preg_replace('/<span class="excerpt-hellip"> \[…\]<\/span>/', '...', $excerpt);

            // If the content contains <figure> with or without a class, remove it, but keep the content inside
            $content = preg_replace('/<figure.*?>(.*?)<\/figure>/s', '$1', $content);

            $converter = new HtmlConverter();

            $postModel->content = $converter->convert($content);
            $postModel->excerpt = $converter->convert($excerpt);

            // For each ![]() which has a url in it, add a newline before and after it
            $postModel->content = preg_replace('/!\[.*?\]\((.*?)\)/', "\n\n![$1]\n\n", $postModel->content);

            $postModel->photo_url = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;

            if($postModel->isDirty()){
                $postModel->save();
                $this->info('Saved post ' . $postModel->id . ' - ' . $postModel->title . '.');
            }
            else{
                $this->info('Post ' . $postModel->id . ' - ' . $postModel->title . ' is unchanged.');
            }
        }

        $this->newLine();

        $this->info("Successfully pulled all {$posts->count()} posts");

        $this->newLine();

        $this->info('Checking to see if any posts need to be deleted...');

        $postIds = $posts->pluck('id');

        $postsToDelete = \App\Models\Post::whereNotIn('wordpress_id', $postIds)->get();

        if($postsToDelete->count() > 0){
            $this->info('Found ' . $postsToDelete->count() . ' posts to delete.');

            foreach($postsToDelete as $postToDelete){
                $this->info('Deleting post ' . $postToDelete->id . ' - ' . $postToDelete->title . '...');
                $postToDelete->delete();
            }
        }
        else{
            $this->info('No posts to delete.');
        }

        $this->newLine(2);

        $this->info('Done');
    }
}
