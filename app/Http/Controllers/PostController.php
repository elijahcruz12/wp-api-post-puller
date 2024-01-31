<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\PostSingleResource;
use App\Models\Post;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $per_page = request()->get('per_page', 10);
        $page = request()->get('page', 1);
        $do_not_paginate = request()->get('do_not_paginate', false);

        if($per_page > 50) {
            $per_page = 50;
        }
        elseif($per_page < 1) {
            $per_page = 1;
        }


        if ($do_not_paginate) {
            $posts = Post::all();
        } else {
            $posts = Post::paginate($per_page, ['*'], 'page', $page);
        }

        if($posts->count() === 0) {
            return response()->json([
                'message' => 'No posts found.'
            ], 404);
        }

        return PostResource::collection($posts);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $postId)
    {
        $post = Post::find($postId);

        if(!$post) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        return new PostSingleResource($post);
    }
}
