<?php

namespace App\Providers;

use App\Models\MaintenanceFinding;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewInstance;

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
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        View::composer('partials.sidebar', function (ViewInstance $view): void {
            /** @var User|null $user */
            $user = request()->user();

            if (! $user) {
                return;
            }

            $viewData = $view->getData();

            if (! array_key_exists('dailyProgress', $viewData)) {
                $todaySchedules = MaintenanceSchedule::query()
                    ->when(
                        $user->role === UserRole::Petugas,
                        fn (Builder $query): Builder => $query->where('assigned_user_id', $user->id),
                    )
                    ->whereDate('scheduled_date', today())
                    ->where('status', '!=', 'cancelled');

                $total = (clone $todaySchedules)->count();
                $completed = (clone $todaySchedules)->where('status', 'completed')->count();

                $view->with('dailyProgress', [
                    'total' => $total,
                    'completed' => $completed,
                    'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                ]);
            }

            if (! array_key_exists('findingActiveCount', $viewData)) {
                $findingActiveCount = MaintenanceFinding::query()
                    ->when(
                        $user->role === UserRole::Petugas,
                        fn (Builder $query): Builder => $query->where('reported_by', $user->id),
                    )
                    ->whereIn('status', ['open', 'review'])
                    ->count();

                $view->with('findingActiveCount', $findingActiveCount);
            }
        });
    }
}
