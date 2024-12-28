<?php
namespace App\Repositories;

use App\Models\Group;

class GroupRepository
{
    public function findById($id)
    {
        return Group::find($id);
    }

    public function getChildGroups($parentId)
    {
        return Group::where('parent_id', $parentId)->get();
    }

    public function create(array $data)
    {
        return Group::create($data);
    }

    public function update($id, array $data)
    {
        return Group::where('id', $id)->update($data);
    }

    public function delete($id)
    {


        $group = Group::findOrFail($id);

        // Check if the group has child groups
        if ($group->children()->count() > 0) {
            throw new \Exception('Cannot delete group because it has child groups.');
        }

        // Check if the group has ledgers
        if ($group->ledgers()->count() > 0) {
            throw new \Exception('Cannot delete group because it has associated ledgers.');
        }

        // Proceed to delete the group
        $group->delete();

        return $group;

    }
}
