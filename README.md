# Wordpress Post Puller

Pulls Wordpress posts from a Wordpress site and saves them as markdown in a database.

# Test Site

https://wp-demo.ectobot.app/

# Goal

To access Wordpress posts from the API and save them as markdown in a database.

# Model

## Post

The post model will contain the following fields:

- id
- wordpress_id
- title
- content
- excerpt
- photo_url
- published_at
- timestamps (laravel)

# How we'll grab the posts

We will use the wordpress API to grab the posts in two scheduled jobs.

## Job 1

The first job will hit /wp-json/wp/v2/posts and grab all the post Ids, excerpts, and titles. It will then save them to the database.

## Job 2

The second job will hit each individual /wp-json/wp/v2/posts/{id} and grab the content, photo_url, and published_at. It will then save them to the database. If the post already exists, it will see if the post data has been updated. If it has, it will update the post.
