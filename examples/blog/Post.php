<?php

use DenOrm\DbCrud;
use DenOrm\Model;

/**
 * The blog post model.
 *
 * @property int $post_id
 * @property string $author
 * @property string $title
 * @property string $text
 */
class Post extends Model
{
    /**
     * Creates and returns the Crud instance for this model.
     *
     * @return \DenOrm\Crud the Crud instance for this model
     */
    public static function createCrudInstance()
    {
        return new DbCrud(get_class(), 'post', 'post_id');
    }
}
