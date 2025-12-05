<?php

namespace App\Providers;

use App\Listeners\SendStoryCommentNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\RolePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\UsersPolicy;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Kirschbaum\Commentions\Events\CommentWasCreatedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(User::class, UsersPolicy::class);
        Model::unguard();

        Event::listen(
            CommentWasCreatedEvent::class,
            SendStoryCommentNotification::class,
        );
    }
}

//YT menit 57:12