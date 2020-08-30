<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class UserRef extends Model
{
    use NodeTrait;

    protected $table = 'user_refs';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
    ];

    public function user() {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
