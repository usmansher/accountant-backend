<?php
namespace App\Services;

use App\Models\Group;
use App\Models\Ledger;
use Illuminate\Support\Facades\Log;

class LedgerTreeService
{
    protected $currentId;
    protected $restrictionBankCash;
    protected $defaultText;
    protected $ledgerList;

    public function __construct($currentId = null, $restrictionBankCash = 1, $defaultText = 'Please select...')
    {
        $this->currentId = $currentId;
        $this->restrictionBankCash = $restrictionBankCash;
        $this->defaultText = $defaultText;
        $this->ledgerList = collect();
    }

    public function buildTree($groupId = 0)
    {
        $group = Group::find($groupId);

        if (!$group && $groupId == 0) {
            // Create a virtual root group
            $group = new Group();
            $group->id = 0;
            $group->name = 'Root';
            $group->parent_id = null;
        } elseif (!$group) {
            // Group not found, return null
            return null;
        }

        // Get child groups
        if ($group->id === 0) {
            $childGroups = Group::whereNull('parent_id')->orderBy('name')->get();
        } else {
            $childGroups = Group::where('parent_id', $group->id)->orderBy('name')->get();
        }

        // Get ledgers under this group
        $ledgers = $this->getLedgers($group);

        $tree = [
            'id' => $group->id,
            'name' => $group->name,
            'children_groups' => [],
            'children_ledgers' => $ledgers,
        ];

        // Recursively build child groups
        foreach ($childGroups as $childGroup) {
            $childTree = $this->buildTree($childGroup->id);
            if ($childTree) {
                $tree['children_groups'][] = $childTree;
            }
        }

        return $tree;
    }

    protected function getLedgers($group)
    {
        $query = Ledger::query();
        Log::info($group);

        if ($group->id !== 0) {
            $query->where('group_id', $group->id);
        } else {
            $query->whereNull('group_id');
        }

        switch ($this->restrictionBankCash) {
            case 4: // Only bank or cash ledgers
                $query->where('type', 1);
                break;
            case 5: // Only non-bank or cash ledgers
                $query->where('type', 0);
                break;
            default:
                // No restriction
                break;
        }

        Log::info($query->orderBy('name')->get());
        return $query->orderBy('name')->get();
    }

    public function toList($tree = null, $depth = 0)
    {
        if (is_null($tree)) {
            $tree = $this->buildTree();
        }

        if ($depth == 0) {
            // Add default text at the top
            $this->ledgerList->put('', $this->defaultText);
        }

        if ($tree && isset($tree['id'])) {
            // Add group name with 'group-' prefix
            $this->ledgerList->put('group-' . $tree['id'], str_repeat('— ', $depth) . $tree['name']);
        } else {
            // If $tree is null or doesn't have 'id', stop processing
            return $this->ledgerList;
        }

        // Add child ledgers
        foreach ($tree['children_ledgers'] as $ledger) {
            $ledgerName = str_repeat('— ', $depth + 1) . $ledger->name;
            $this->ledgerList->put($ledger->id, $ledgerName);
        }

        // Process child groups recursively
        foreach ($tree['children_groups'] as $childTree) {
            $this->toList($childTree, $depth + 1);
        }

        return $this->ledgerList;
    }
}
