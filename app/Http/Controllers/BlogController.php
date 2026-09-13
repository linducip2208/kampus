<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Response;

class BlogController extends Controller
{
    public function index() { return view('blog.index', ['posts' => BlogPost::with('category')->published()->latest('published_at')->paginate(9), 'categories' => BlogCategory::withCount('posts')->get()]); }
    public function category(string $slug) { $category = BlogCategory::where('slug', $slug)->firstOrFail(); return view('blog.index', ['posts' => $category->posts()->with('category')->published()->latest('published_at')->paginate(9), 'categories' => BlogCategory::withCount('posts')->get(), 'activeCategory' => $category]); }
    public function show(string $slug) { $post = BlogPost::with(['category', 'author'])->published()->where('slug', $slug)->firstOrFail(); return view('blog.show', compact('post')); }
    public function feed() { $posts = BlogPost::published()->latest('published_at')->limit(20)->get(); return Response::view('blog.feed', compact('posts'))->header('Content-Type', 'application/rss+xml'); }
}
