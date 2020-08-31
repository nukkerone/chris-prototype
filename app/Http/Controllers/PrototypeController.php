<?php

namespace App\Http\Controllers;

use App\Flower;
use App\User;
use App\UserRef;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class PrototypeController extends Controller
{
    public $flower;

    public function __construct() {
        $flower = Flower::query()->first();

        if (!$flower) {
            $userRef = new UserRef();
            $userRef->save();

            $flower = new Flower(['root_user_ref_id' => $userRef->id]);
            $flower->save();
            $flower->refresh();
        }
        $this->flower = $flower;
    }

    public function index() {
        $allUsers = $this->flower->getUsers();
        $userRef = $this->flower->flowerRoot()->with('user')->get();

        $tree = $userRef->map(function($ref) {
            $tree = $ref->descendants()->with('user')->get()->toTree();
            return $ref->setRelation('children', $tree)->unsetRelation('descendants');
        });

        return view('prototype.index', [
            'flower' => $this->flower,
            'tree' => addslashes($tree->toJson()),
            'users' => $allUsers->pluck('user'),
            'all_users_qty' => User::query()->count(),
            'unassigned_user_qty' => User::query()->where('unassigned', '=', true)->count(),
        ]);
    }

    public function showUser($id) {
        $user = User::find($id);
        $userRefs = UserRef::query()->with('user')->withDepth()->where('user_id', '=', $user->id)->get();
        $userRef = $userRefs->last();
        if (!$userRef)  return;
        $depth = $userRef->depth;
        $week = $this->flower->current_week;

        if ($depth == $week - 1) $subflowerRoot = new Collection([$userRef]);
        else    $subflowerRoot = UserRef::withDepth()->with('user')->having('depth', '=', ($week - 1))->ancestorsOf($userRef->id);

        $tree = $subflowerRoot->map(function($ref) use ($week) {
            $tree = $ref->descendants()->with('user')->withDepth()->having('depth', '<', $week + 3)->get()->toTree();
            return $ref->setRelation('children', $tree)->unsetRelation('descendants');
        });

        return view('prototype.user', [
            'flower' => $this->flower,
            'tree' => addslashes($tree->toJson()),
            'user' => $user,
            'position' => User::readablePosition($week, $depth),
            /*'users' => $allUsers->pluck('user'),*/
            /*'unassigned_user_qty' => User::query()->where('unassigned', '=', true)->count(),*/
        ]);
    }

    public function createUsers() {
        $quantity = request()->has('qty') ? request()->get('qty') : 10;
        $usersData = [];
        $faker = FakerFactory::create();
        $i = 1;

        while ($i <= $quantity) {
            $name = $faker->name;
            $userData = [
                'name' => $name,
                'email' => $faker->email,
                'password' => $name,
            ];
            $usersData[] = $userData;
            $i++;
        }

        try {
            $users = User::insert($usersData);
        } catch (\Exception $e) {}

        return redirect('prototype')->with('success', 'Users Generated');
    }

    public function assignUsers() {
        return $this->_assignUsers($this->flower->id);
    }

    public function _assignUsers($flowerId) {
        $assignableUsers = User::query()->where('unassigned', '=', true)->get();
        $flower = Flower::query()->find($flowerId);
        $missingUsers = false;

        if ($this->flower->current_week) {
            $from = $this->flower->current_week;
            $to = $from + 2;
        } else {
            $from = 0;
            $to = 2;
            if ($assignableUsers->count() == 0) return null;
            $user = $assignableUsers->first();
            $userRef = $flower->flowerRoot()->withDepth()->having('depth', '=', $from)->first();
            if (!$userRef->user_id) {
                $userRef->user_id = $user->id;
                $userRef->save();
                $user->unassigned = false;
                $user->save();
                $assignableUsers = $assignableUsers->splice(1);
            }
        }

        $i = $from;
        while ($i <= $to) {
            $userRefs = UserRef::withDepth()->having('depth', '=', $i)->get();
            $userRefs->each(function($userRef) use (&$assignableUsers, $i, $to, $from, &$missingUsers) {
                // Calculate missing children
                $childrenCount = $userRef->children->count();
                $missing = 2 - $childrenCount;
                $missingIndex = 0;
                // Add last week water, to the list of assignable users.
                if ($i == $from && $i > 0) {
                    $assignableParent = $userRef->parent->user;
                    if ($assignableParent && !$assignableUsers->firstWhere('id', '=', $assignableParent->id))  $assignableUsers->prepend($assignableParent);
                }
                // If we are filling fires, Get the root (or userRef in water position) for payment
                if ($i == $to) {
                    $rootRef = UserRef::withDepth()->having('depth', '=', $from)->ancestorsOf($userRef->id);
                    $rootRef = $rootRef[0];
                }
                // Fill with the missing children (this iterates 2 times max):
                while ($missingIndex < $missing) {
                    if ($assignableUsers->count() == 0) { $missingUsers = true; continue; }
                    $user = $assignableUsers->first();
                    $node = new UserRef(['user_id' => $user->id]);
                    $userRef->appendNode($node);
                    $user->unassigned = false;
                    if ($i == $to) {
                        $user->wallet = $user->wallet - $this->flower->enter_payment;
                        if (isset($rootRef) && $rootRef) {
                            $rootUser = $rootRef->user;
                            $rootUser->wallet += $this->flower->enter_payment;
                            $rootUser->save();
                        }
                        $this->flower->accumulated_payments += $this->flower->enter_payment;
                        $this->flower->save();
                    }
                    $user->save();
                    $assignableUsers = $assignableUsers->splice(1, $assignableUsers->count() - 1);
                    $missingIndex++;
                }
            });
            $i++;
        }

        // If all missing users were added, we're ready to progress to next week
        if (!$missingUsers) {
            if (!$this->flower->current_week)   $this->flower->current_week = 1;
            else $this->flower->current_week++;
            $this->flower->save();
            // UserRefs in water position need to be set as unassigned
            $waterRefs = $flower->flowerRoot()->withDepth()->having('depth', '=', $from)->get();
            $waterRefs->each(function($waterRef) {
                $waterRef->user->unassigned = false;
                $waterRef->user->save();
            });
            return redirect('prototype')->with('success', 'Users assigned and Week advanced');
        } else {
            return redirect('prototype')->with('success', 'Users assigned, but there are still missing users to complete flower and advance te Week.');
        }

    }

    public function resetDatabase() {
        Artisan::call('migrate:refresh');

        return redirect('prototype')->with('success', 'Database Reset');
    }
}
