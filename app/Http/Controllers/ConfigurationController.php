<?php

namespace App\Http\Controllers;

use App\Repositories\ConfigurationRepository;

class ConfigurationController extends Controller
{
    protected $request;
    protected $repo;
    protected $module = 'configuration';


    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct(ConfigurationRepository $repo)
    {
        $this->repo = $repo;
    }


    public function systemConfiguration()
    {
        $user = request()->user();
        $system_variables = getVar('system');

        return response()->json([
            'system_variables' => $system_variables,
            'config' => $this->repo->getAllPublic(),
        ]);
    }


}
