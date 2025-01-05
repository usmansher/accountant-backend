<?php
namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index()
    {
        return response()->json(Role::all(), 200);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->roleRepository->create([
            'name' => $data['name'],
            'guard_name' => 'api'
        ]);
        return response()->json(['message' => 'Role created successfully']);
    }

    public function show($id)
    {
        $role = $this->roleRepository->findById($id);
        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->roleRepository->update($id, $data);
        return response()->json(['message' => 'Role updated successfully']);
    }

    public function destroy($id)
    {
        try {
            $this->roleRepository->delete($id);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 400);
        }

        return response()->json(['message' => 'Role deleted successfully']);
    }


    // GET /api/role/{role}/permissions
    public function getPermissions(Role $role)
    {
        // All available permissions
        $allPermissions = Permission::all();

        // Permission IDs assigned to this role
        $assignedPermissions = $role->permissions->pluck('id')->toArray();

        return response()->json([
            'permissions' => $allPermissions,
            'rolePermissions' => $assignedPermissions,
        ]);
    }

    // POST /api/role/{role}/permissions
    public function updatePermissions(Request $request, Role $role)
    {
        // Validate incoming permission IDs if needed
        $data = $request->validate([
            'permissions' => 'required|array',
        ]);
        $role->syncPermissions($data['permissions']);

        return response()->json([
            'message' => 'Permissions updated successfully',
        ]);
    }
}
