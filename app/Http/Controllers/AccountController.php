<?php

namespace App\Http\Controllers;

use App\Events\AccountCreated;
use App\Models\Account;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Account::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'email' => 'required|string|email|max:255|unique:accounts',
            'currency_symbol' => 'nullable|string|max:10',
            'currency_format' => 'nullable|string|max:10',
            'decimal_places' => 'nullable|integer|max:10',
            'date_format' => 'nullable|string|max:10',
            'fy_start' => 'nullable|date',
            'fy_end' => 'nullable|date',
            'db_datasource' => 'nullable|string|max:255',
            'db_database' => 'nullable|string|max:255',
            'db_schema' => 'nullable|string|max:255',
            'db_host' => 'nullable|string|max:255',
            'db_port' => 'nullable|string|max:10',
            'db_login' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'db_prefix' => 'nullable|string|max:255',
            'db_persistent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->merge([
            'id' => (string) Str::slug($request->label),
        ]);

        $account = Account::create($data->all());
        try {
            // event(new AccountCreated($account));
            return response()->json($account, 201);
        } catch (Exception $e) {
            $account->delete();
            Log::error('Account creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        return response()->json($account, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Account $account)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'email' => 'sometimes|required|string|email|max:255|unique:accounts,email,' . $account->id,
            'currency_symbol' => 'nullable|string|max:10',
            'currency_format' => 'nullable|string|max:10',
            'decimal_places' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:10',
            'fy_start' => 'nullable|date',
            'fy_end' => 'nullable|date',
            'db_datasource' => 'nullable|string|max:255',
            'db_database' => 'nullable|string|max:255',
            'db_schema' => 'nullable|string|max:255',
            'db_host' => 'nullable|string|max:255',
            'db_port' => 'nullable|string|max:10',
            'db_login' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'db_prefix' => 'nullable|string|max:255',
            'db_persistent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $account->update($request->all());

        return response()->json($account, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return response()->json(null, 204);
    }

    // activate the account,
    public function activate(Account $account)
    {
        Cookie::make('active_account_id', $account->id, 30);
        // Cache::put('active_account_id', $account->id, now()->addMinutes(30));
        return response()->json(['message' => 'Active account set successfully']);
    }
}
