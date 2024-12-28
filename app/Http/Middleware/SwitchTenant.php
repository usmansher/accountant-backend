<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SwitchTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $accountId = Cache::get('active_account_id');
        $accountId = Cookie::get('active_account_id') ?? 'ots';
        Log::info('SwitchTenant: accountId=' . $accountId);
        if ($accountId) {
            $account = Account::find($accountId);
            if ($account) {
                tenancy()->initialize($account);
            }
        }
        return $next($request);
    }
}
