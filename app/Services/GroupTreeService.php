<?php
// app/Services/GroupTreeService.php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Collection;

class GroupTreeService
{
    protected $currentId;
    protected $groupList;

    public function __construct($currentId = null)
    {
        $this->currentId = $currentId;
        $this->groupList = collect();
    }

    /**
     * Build the group tree starting from a specific group ID.
     */
    public function buildTree($groupId = null)
    {
        $group = $groupId ? Group::find($groupId) : null;
        return $this->buildNode($group);
    }

    /**
     * Recursively build the tree node.
     */
    protected function buildNode($group)
    {
        $node = [
            'id'                => $group->id ?? null,
            'name'              => $group->name ?? 'None',
            'code'              => $group->code ?? '',
            'children_groups'   => [],
        ];

        // Prevent infinite loops by checking the current ID
        if ($this->currentId && $this->currentId == $group->id) {
            return $node;
        }

        // Get child groups
        $childGroups = Group::where('parent_id', $group->id ?? null)->orderBy('name')->get();

        foreach ($childGroups as $childGroup) {
            $childNode = $this->buildNode($childGroup);
            $node['children_groups'][] = $childNode;
        }

        return $node;
    }

    /**
     * Convert the group tree to a flat list with indentation.
     */
    public function toList($tree = null, $depth = 0)
    {
        if (is_null($tree)) {
            $tree = $this->buildTree();
        }

        if ($tree['id']) {
            // Use negative IDs for groups to distinguish them from ledgers (if needed)
            $this->groupList->put($tree['id'], str_repeat('— ', $depth) . $tree['name']);
        }

        foreach ($tree['children_groups'] as $childTree) {
            $this->toList($childTree, $depth + 1);
        }

        return $this->groupList;
    }
}
