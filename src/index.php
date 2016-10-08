<?php

// Silex documentation: http://silex.sensiolabs.org/doc/
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';


$app = new Silex\Application();

$app['debug'] = true;

/**
 * SQLite config
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
        return $app->json(array('msg' => 'FAIL', 'error' => "User $user_id does not exist."), 404);
    }

    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.user_id = ?";
    $posts = $app['db']->fetchAll($sql, array((int)$user_id));

    return $app->json($posts, 200);
});

$app->get('/api/posts/id/{post_id}', function ($post_id) use ($app) {
    $sql = "SELECT posts.rowid, posts.*, users.name FROM posts INNER JOIN users ON users.user_id = posts.user_id WHERE posts.rowid = ?";
    $post = $app['db']->fetchAssoc($sql, array((int)$post_id));

    if (!$post) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Post $post_id does not exist."), 404);
    }
    return $app->json($post, 200);
});

$app->post('/api/posts/new', function (Request $request) use ($app) {
    //TODO
    $user_id = intval($request->get('user'));
    $content = $request->get('content');

    //check if this user exist
    $sql = "SELECT user_id FROM users WHERE user_id = ?";
    $user = $app['db']->fetchAssoc($sql, array((int)$user_id));
    if (!$user) {
        return $app->json(array('msg' => 'FAIL', 'error' => "User $user_id does not exist."), 400);
    }

    //check the content is less than 100
    $str = preg_replace('/\s+/', ' ', $content);
    if (strlen($str) < 100) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Content length must be more than 100 characters"), 400);
    }

    //save the post
    $insertPost = $app['db']->insert('posts', array('user_id' => $user_id, 'content' => $content, 'date' => strtotime('now')));
    if (!$insertPost) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Insertion failed , try again later"), 400);
    }

    //get the id and return a success response
    $id = $app['db']->lastInsertId();

    return $app->json(array('msg' => 'OK', 'id' => $id));


});

$app->put('/api/posts/edit/{post_id}', function ($post_id, Request $request) use ($app) {

    $user_id = intval($request->get('user'));
    $content = $request->get('content');

    //check if this blog exist
    $sql = "SELECT rowid FROM posts WHERE rowid = ?";
    $user = $app['db']->fetchAssoc($sql, array((int)$post_id));
    if (!$user) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Post $post_id does not exist."), 404);
    }

    //check if this user exist
    $sql = "SELECT user_id FROM users WHERE user_id = ?";
    $user = $app['db']->fetchAssoc($sql, array((int)$user_id));
    if (!$user) {
        return $app->json(array('msg' => 'FAIL', 'error' => "User $user_id does not exist."), 400);
    }

    //check the content is less than 100
    $str = preg_replace('/\s+/', ' ', $content);
    if (strlen($str) < 100) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Content length must be more than 100 characters"), 400);
    }

    //save the post
    $updatePost = $app['db']->update('posts', array('user_id' => $user_id, 'content' => $content), array('rowid' => (int)$post_id));
    if (!$updatePost) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Update failed, try again later"), 400);
    }

    //return a success response
    return $app->json(array('msg' => 'OK', 'id' => $post_id));
});

$app->delete('/api/posts/delete/{id}', function ($id) use ($app) {
    $id = 15;
    $sql = "SELECT posts.rowid FROM posts  WHERE posts.rowid = ?";
    $post = $app['db']->fetchAssoc($sql, array((int)$id));

    if (!$post) {
        return $app->json(array('msg' => 'FAIL', 'error' => "Post $id does not exist."), 404);
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

$app->get('/posts/{post_id}', function ($post_id) use ($app) {
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



/*
    Add a users table to sqlite db //Done

    Error checking - e.g. if try retrieve posts for a user_id that does not exist,
        return an error message and an appropriate HTTP status code. //Done

    Implement /api/posts/new endpoint to add a new micro-blog post for a given user. //Done

    Extra: Add new API endpoints for any extra features you can think of. //Done

    Extra: Improve on current API code where you see necessary //Done

    Build front-end of web app in the / endpoint below - Add more endpoints if you like. //Done

    Build the front-end for the micro-blog using a front-end framework of your choice. //Done
*/