<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\Post;
use App\Models\Project;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index() {
        $sitemap = Sitemap::create();

        //Static pages
        $sitemap->add(Url::create('/')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(1.0));

        $sitemap->add(Url::create('/projects')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9));

        $sitemap->add(Url::create('/blog')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.8));

        $sitemap->add(Url::create('/contact')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7));

        // Projects
        Project::published()->get()->each(function ($project) use ($sitemap) {
            $sitemap->add(Url::create("/projects/{$project->slug}")
                ->setLastModificationDate($project->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.8));
        });

        // Blog posts
        Post::published()->get()->each(function ($post) use ($sitemap) {
            $sitemap->add(Url::create("/blog/{$post->slug}")
            ->setLastModificationDate($post->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7));
        });

        return $sitemap->toResponse(request());
    }
}
