SJF WP API Shortcode
=========

The purpose of this plugin is to give developers a simple block of code for "hello-world-ing" the new WP API:  http://v2.wp-api.org/

As of WordPress 4.3, the JSON API is not part of core, so this plugin dies if the blog does not have the JSON API plugin from Ryan McCue: https://wordpress.org/plugins/rest-api/

Example shortcode uses:

 * Default form for pinging the api root: [wp_api]
 * Browse posts: [wp_api route=posts]
 * Add post: [wp_api method="POST" route='posts' data=' { "title":"horace", "content": "grant" } ']
 * Browse users: [wp_api route=users]