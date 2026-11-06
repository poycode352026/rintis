<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    public function view(User $user, Store $store): Response
    {
        return $store->owner_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Store $store): Response
    {
        return $this->view($user, $store);
    }
}
