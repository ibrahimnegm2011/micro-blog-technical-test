<?php

// Silex documentation: http://silex.sensiolabs.org/doc/

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

/* SQLite config

TODO: Add a users table to sqlite db
*/

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/app.db',
    ),
));

// Twig template engine config
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));


/* ------- micro-blog api ---------

All CRUD operations performed within our /api/ endpoints below

TODO: Error checking - e.g. if try retrieve posts for a user_id that does
      not exist, return an error message and an appropriate HTTP status code.

      Implement /api/posts/new endpoint to add a new micro-blog post for a
      given user.

      Extra: Add new API endpoints for any extra features you can think of.

      Extra: Improve on current API code where you see necessary
*/

$app->get('/api/posts', function () use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id";
    $posts = $app['db']->fetchAll($sql);

    return $app->json($posts, 200);
});

$app->get('/api/posts/user/{user_id}', function ($user_id) use ($app) {
    //check if this user exist
    $sql = "SELECT user_id FROM users WHERE user_id = ?";
    $user = $app['db']->fetchAssoc($sql, array((int)$user_id));
    if (!$user) {
        $app->abort(404, "User $user_id does not exist.");
    }

    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.user_id = ?";
    $posts = $app['db']->fetchAll($sql, array((int)$user_id));

    return $app->json($posts, 200);
});

$app->get('/api/posts/id/{post_id}', function ($post_id) use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.rowid = ?";
    $post = $app['db']->fetchAssoc($sql, array((int)$post_id));

    if (!$post) {
        $app->abort(404, "Post $post_id does not exist.");
    }
    return $app->json($post, 200);
});

$app->post('/api/posts/new', function (Request $request) {
    //TODO
});

$app->delete('/api/posts/delete/{id}', function ($id) use ($app) {
    $sql = "SELECT posts.rowid FROM posts  WHERE posts.rowid = ?";
    $post = $app['db']->fetchAssoc($sql, array((int)$id));

    if (!$post) {
        $app->abort(404, "Post $id does not exist.");
    }

    $sql = "DELETE FROM posts WHERE posts.rowid = ?";
    $deletedCount = $app['db']->executeUpdate($sql, array((int)$id));

    return $app->json(['msg' => 'OK', 'deleted_rows' => $deletedCount], 200);
});

$app->get('/api/users', function () use ($app) {
    $sql = "SELECT users.* FROM users";
    $users = $app['db']->fetchAll($sql);

    return $app->json($users, 200);
});


/* ------- micro-blog web app ---------

All Endpoints for micro-blog web app below.

TODO: Build front-end of web app in the / endpoint below - Add more
      endpoints if you like.

      See TODO in index.twig for more instructions / suggestions
*/

$app->get('/', function () use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id";
    $posts = $app['db']->fetchAll($sql);

    return $app['twig']->render('index.twig', [
        'title' => "All Posts",
        'posts' => $posts
    ]);
});

$app->get('/posts/user/{user_id}', function ($user_id) use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.user_id = ?";
    $posts = $app['db']->fetchAll($sql, array((int)$user_id));
    $user_name = $posts[0]['name'];

    return $app['twig']->render('index.twig', [
        'title' => "All Posts for {$user_name}",
        'posts' => $posts
    ]);
});

$app->get('/post/id/{post_id}', function ($post_id) use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.rowid = ?";
    $post = $app['db']->fetchAssoc($sql, array((int)$post_id));

    if (!$post) {
        $app->abort(404, "Post $post_id does not exist.");
    }
    return $app['twig']->render('post.twig', [
        'title' => "Post #{$post['rowid']}",
        'post' => $post
    ]);
});


$app->run();
