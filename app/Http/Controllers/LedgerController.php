<?php
namespace App\Http\Controllers;

use App\Http\Requests\LedgerRequest;
use App\Services\GroupTreeService;
use App\Repositories\LedgerRepository;
use App\Services\LedgerTreeService;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Request;

class LedgerController extends Controller
{
    protected $groupTreeService;
    protected $ledgerRepository;
    protected $ledgerTreeService;

    public function __construct(GroupTreeService $groupTreeService, LedgerRepository $ledgerRepository, LedgerTreeService $ledgerTreeService)
    {
        $this->groupTreeService = $groupTreeService;
        $this->ledgerRepository = $ledgerRepository;
        $this->ledgerTreeService = $ledgerTreeService;
    }

    public function index()
    {

        $ledgerTreeService = new LedgerTreeService();
        $ledgerList = $ledgerTreeService->toList();
        return response()->json($ledgerList);
    }

    public function preRequisite() {
        $parentGroups = $this->groupTreeService->buildTree(0);
        $parentGroupList = $this->groupTreeService->toList($parentGroups);

        return response()->json([
            'parentGroupList' => $parentGroupList
        ]);
    }

    public function store(LedgerRequest $request)
    {
        $this->ledgerRepository->create($request->all());
        return response()->json(['message' => 'Ledger created successfully']);
    }

    public function show($id)
    {
        $group = $this->ledgerRepository->findById($id);
        return response()->json($group);
    }

    public function update(LedgerRequest $request, $id)
    {

        $this->ledgerRepository->update($id, $request->all());
        return response()->json(['message' => 'Ledger updated successfully']);
    }

    public function destroy($id)
    {
        try {
            $this->ledgerRepository->delete($id);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 400);
        }
        return response()->json(['message' => 'Ledger deleted successfully']);
    }


    public function ledgerList(HttpRequest $request)
    {
        $restrictionBankCash = $request->input('restriction_bankcash', 1);

        $ledgerTreeService = new LedgerTreeService(-1, $restrictionBankCash);
        $ledgerList = $ledgerTreeService->toList();

        $response = [];
        foreach ($ledgerList as $id => $name) {
            $disabled = false;
            if (strpos($id, 'group-') === 0) {
                $disabled = true;
            }
            $response[] = [
                'id' => $id,
                'name' => $name,
                'disabled' => $disabled,
            ];
        }

        return response()->json($response);

        return response()->json([
            'ledgerList' => $response,
            'ledgersDisabled' => $ledgersDisabled,
        ]);
    }
}


