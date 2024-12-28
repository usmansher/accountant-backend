<?php
namespace App\Http\Controllers;

use App\Services\GroupTreeService;
use App\Repositories\GroupRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected $groupTreeService;
    protected $groupRepository;

    public function __construct(GroupTreeService $groupTreeService, GroupRepository $groupRepository)
    {
        $this->groupTreeService = $groupTreeService;
        $this->groupRepository = $groupRepository;
    }

    public function index()
    {
        $groupTreeService = new GroupTreeService();
        $groupList = $groupTreeService->toList();
        return response()->json($groupList);
    }

    public function preRequisite() {
        $parentGroups = $this->groupTreeService->buildTree(0);
        $parentGroupList = $this->groupTreeService->toList($parentGroups);

        return response()->json([
            'parentGroupList' => $parentGroupList
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:groups,id'
        ]);

        $this->groupRepository->create($data);
        return response()->json(['message' => 'Group created successfully']);
    }

    public function show($id)
    {
        $group = $this->groupRepository->findById($id);
        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:groups,id'
        ]);

        $this->groupRepository->update($id, $data);
        return response()->json(['message' => 'Group updated successfully']);
    }

    public function destroy($id)
    {
        try {
            $this->groupRepository->delete($id);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 400);
        }

        return response()->json(['message' => 'Group deleted successfully']);
    }
}
