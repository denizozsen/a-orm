<?php

/*

  Table and data for this example

    CREATE table post (
        post_id INT(11) NOT NULL AUTO_INCREMENT,
        author VARCHAR(50) NOT NULL,
        title VARCHAR(250) NOT NULL,
        text TEXT NOT NULL,
        PRIMARY KEY (post_id)
    ) ENGINE=InnoDB;

    INSERT INTO post (author, title, text) VALUES
        ('Deniz', 'Post 1', 'Text 1 ...'),
        ('Deniz', 'Post 2', 'Text 2 ...'),
        ('Someone Else', 'My Awesome Post', 'This is my awesome text!'),
        ('Another Author', 'Great Post', 'This is a great post!');

*/

// Include composer's class auto loader
require __DIR__ . '/../../vendor/autoload.php';

// Include our Post model class
require 'Post.php';

// Newline appropriate for either CLI or web output
$nl = (PHP_SAPI == 'cli') ? PHP_EOL : ('<br>' . PHP_EOL);

// Set up the db connection with MySQL, either using mysqli or PDO
$host = '127.0.0.1';
$db = 'test';
$user = 'my_user';
$password = 'my_password';
//$mysqli = new mysqli($host, $user, $password, $db);
$dsn = "mysql:dbname={$db};host={$host}";
$pdo = new PDO($dsn, $user, $password);

// Tell DenOrm to use the mysqli/PDO instance we've just created
//\DenOrm\Registry::registerMysqliConnection($mysqli);
\DenOrm\Registry::registerPdoConnection($pdo);

// Fetch all Post records where the author is set to 'Deniz'
$my_posts = Post::fetchAll(['author' => 'Deniz']);

// You can access the fields of each row via property getters on the model objects, like this:
echo 'Retrieve values via property getters:' . $nl;
foreach ($my_posts as $post) {
    var_dump([
        'author' => $post->author,
        'title' => $post->title,
        'text' => $post->text
    ]);
}

// You can also get all of a row's fields, by calling getData():
echo $nl . $nl . 'All row data via getData()' . $nl;
$data = $my_posts[0]->getData();
var_dump($data);

