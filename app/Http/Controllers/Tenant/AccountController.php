<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\AccountTreeService;

class AccountController extends Controller
{

    protected $accountTreeService;

    public function __construct()
    {
        $this->accountTreeService = new AccountTreeService();
    }

    public function index()
    {
        $accountTree = $this->accountTreeService->getAccountTree();
        return $accountTree;
    }

}
