<?php
namespace App\Repositories;

use App\Models\Role;
use Illuminate\Validation\ValidationException;

class RoleRepository
{
    public function findById($id)
    {
        return Role::find($id);
    }

    public function create(array $data)
    {
        return Role::create($data);
    }

    public function update($id, array $data)
    {
        return Role::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $role = Role::findOrFail($id);


        // Check if the role has any entries
        if ($role->entries()->count() > 0) {
            throw ValidationException::withMessages([
                'role' => 'Cannot delete role because it has associated entries.'
            ]);
        }

        // Proceed to delete the role
        $role->delete();

        return true;

    }
}
