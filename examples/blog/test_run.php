<?php

//
// This test program assumes a post table that looks like this:
//
//     CREATE table post (
//         post_id INT(11) NOT NULL AUTO_INCREMENT,
//         author VARCHAR(50) NOT NULL,
//         title VARCHAR(250) NOT NULL,
//         text TEXT NOT NULL,
//         PRIMARY KEY (post_id)
//     ) ENGINE=InnoDB;
//

// Set up class autoloader for DenOrm
// NOTE: once DenOrm has the proper composer config, this part will not be necessary any more
spl_autoload_register(function ($class) {
    // base directory for the namespace prefix
    $base_dir = realpath(__DIR__ . '/../../src');

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . '/' . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Require our Post model class
require 'Post.php';

// Set up the db connection with PDO
$dsn = 'mysql:dbname=test;host=127.0.0.1';
$user = 'root';
$password = '';
$pdo = new PDO($dsn, $user, $password);

// Tell DenOrm to use the PDO instance we've just created
\DenOrm\Registry::registerPdoConnection($pdo);

// Fetch all Post records where the author is set to 'Deniz'
$my_posts = Post::fetchAll(['author' => 'Deniz']);

// Dump the returned model instances
var_dump($my_posts);
