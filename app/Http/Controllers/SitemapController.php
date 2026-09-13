<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\BlogPost;
use App\Models\StudyProgram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addDay(), function () {
            $urls = collect([url('/'), url('/docs'), url('/login'), route('blog'), route('faq'), route('contact')])
                ->merge(StudyProgram::all()->map(fn ($item) => url('/program/'.$item->id)))
                ->merge(Course::all()->map(fn ($item) => url('/course/'.$item->id)));
            $urls = $urls->merge(BlogPost::published()->get()->map(fn ($item) => route('blog.show', $item->slug)));
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
                $urls->map(fn ($url) => '<url><loc>'.e($url).'</loc><changefreq>weekly</changefreq></url>')->implode('').
                '</urlset>';
        });
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        return Response::make("User-agent: *\nAllow: /$\nAllow: /docs\nAllow: /program/\nAllow: /course/\nAllow: /blog\nAllow: /faq\nAllow: /contact\nDisallow: /admin\nDisallow: /api\nDisallow: /webhooks\nSitemap: /sitemap.xml\n", 200, ['Content-Type' => 'text/plain']);
    }
}
