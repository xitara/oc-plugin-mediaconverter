<?php namespace Xitara\MediaConverter\Models;

use Model;

/**
 * MediaList Model
 */
class MediaList extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'xitara_mediaconverter_media_lists';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
}
