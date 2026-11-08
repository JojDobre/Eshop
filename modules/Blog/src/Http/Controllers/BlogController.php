<?php

namespace Modules\Blog\Http\Controllers;

use App\Core\Content\AlternateUrls;
use App\Core\Themes\ThemeManager;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Blog\BlogSettings;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\PostCategory;
use Modules\Blog\Models\PostTag;

class BlogController extends Controller
{
    public function index(): View
    {
        app(AlternateUrls::class)->using(fn () => route('blog.index'));

        return $this->listing(Post::query()->published());
    }

    public function category(string $slug): View
    {
        $category = PostCategory::query()->whereSlug($slug)->firstOrFail();
        app(AlternateUrls::class)->using(fn () => $category->url());

        return $this->listing(Post::query()->published()->where('blog_category_id', $category->id), ['blogCategory' => $category]);
    }

    public function tag(string $slug): View
    {
        $tag = PostTag::query()->whereSlug($slug)->firstOrFail();
        app(AlternateUrls::class)->using(fn () => $tag->url());

        return $this->listing(Post::query()->published()->whereHas('tags', fn ($query) => $query->whereKey($tag->id)), ['blogTag' => $tag]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()->published()->whereSlug($slug)->firstOrFail();
        app(AlternateUrls::class)->using(fn () => $post->url());

        return $this->post($post);
    }

    /** Náhľad konceptu alebo naplánovaného článku (odkaz z administrácie, neindexuje sa). */
    public function preview(string $token): View
    {
        return $this->post(Post::query()->where('preview_token', $token)->firstOrFail(), preview: true);
    }

    public function rss(): Response
    {
        $posts = Post::query()->published()->latest('published_at')->limit(20)->get();

        return response()->view('blog::rss', ['posts' => $posts, 'shop' => app(GeneralSettings::class)], 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * @param  Builder<Post>  $query
     * @param  array<string, mixed>  $data
     */
    private function listing($query, array $data = []): View
    {
        $posts = $query->with(['category', 'author'])->latest('published_at')->paginate(max(1, app(BlogSettings::class)->posts_per_page))->withQueryString();

        return view(app(ThemeManager::class)->template('blog'), [
            ...$data,
            'posts' => $posts,
            'blogCategories' => PostCategory::query()->orderBy('position')->get(),
        ]);
    }

    private function post(Post $post, bool $preview = false): View
    {
        $post->loadMissing(['category', 'author', 'tags', 'products.prices', 'comments' => fn ($query) => $query->approved()->oldest()]);
        $content = $post->contentWithToc();

        return view(app(ThemeManager::class)->template('blog-post', ['blog-post-'.$post->slug]), [
            'post' => $post,
            'blocks' => $content['blocks'],
            'toc' => $content['toc'],
            'preview' => $preview,
            'commentsOpen' => ! $preview && $post->comments_enabled && app(BlogSettings::class)->comments_enabled,
            'relatedPosts' => Post::query()->published()->whereKeyNot($post->id)
                ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
                ->latest('published_at')->limit(3)->get(),
        ]);
    }
}
