<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('view groups');
    }

    public function view(User $user, Group $group)
    {
        return $user->hasPermission('view group');
    }

    public function create(User $user)
    {
        return $user->hasPermission('create group');
    }

    public function update(User $user, Group $group)
    {
        return $user->hasPermission('update group');
    }

    public function delete(User $user, Group $group)
    {
        return $user->hasPermission('delete group');
    }
}
