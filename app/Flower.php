<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserRef;

class Flower extends Model
{

    protected $table = 'flowers';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'current_week', 'enter_payment', 'accumulated_payments', 'root_user_ref_id'
    ];

    public function flowerRoot() {
        return $this->hasOne('App\UserRef', 'id', 'root_user_ref_id');
    }

    public function getUsers() {
        $rootRef = $this->flowerRoot()->first();

        return UserRef::with('user')->whereNotNull('user_id')->descendantsAndSelf($rootRef->id);
    }
}
