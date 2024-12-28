<?php

namespace App\Services;

use App\Helpers\AccountingHelper;
use App\Models\Group;
use App\Models\Ledger;
use Illuminate\Support\Facades\Log;

class AccountTreeService
{
    protected $onlyOpening;
    protected $startDate;
    protected $endDate;
    protected $affectsGross;

    public function __construct($onlyOpening = false, $startDate = null, $endDate = null, $affectsGross = -1)
    {
        $this->onlyOpening = $onlyOpening;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->affectsGross = $affectsGross;
    }

    public function getAccountTree($groupId = null)
    {
        $group = $groupId ? Group::find($groupId) : null;
        return $this->buildTree($group);
    }

    protected function buildTree($group)
    {
        $tree = [
            'id'                => $group->id ?? null,
            'name'              => $group->name ?? 'None',
            'code'              => $group->code ?? '',
            'g_parent_id'       => $group->parent_id ?? null,
            'g_affects_gross'   => $group->affects_gross ?? null,
            'op_total'          => 0.00,
            'op_total_dc'       => 'D',
            'dr_total'          => 0.00,
            'cr_total'          => 0.00,
            'cl_total'          => 0.00,
            'cl_total_dc'       => 'D',
            'children_groups'   => [],
            'children_ledgers'  => [],
        ];

        // Add sub ledgers if applicable
        if ($this->affectsGross != 1) {
            $this->addSubLedgers($tree, $group);
        }

        // Add sub groups
        $this->addSubGroups($tree, $group);

        return $tree;
    }

    protected function addSubGroups(&$tree, $group)
    {
        $query = Group::query();

        if ($group) {
            $query->where('parent_id', $group->id);
        } else {
            $query->whereNull('parent_id');
        }

        if ($this->affectsGross == 0 || $this->affectsGross == 1) {
            $query->where('affects_gross', $this->affectsGross);
        }

        $childGroups = $query->orderBy('name')->get();

        foreach ($childGroups as $childGroup) {
            $childTree = $this->buildTree($childGroup);

            // Update totals
            $tree = $this->updateTotals($tree, $childTree);

            $tree['children_groups'][] = $childTree;
        }
    }

    protected function addSubLedgers(&$tree, $group)
    {
        $childLedgers = $group ? $group->ledgers()->orderBy('name')->get() : Ledger::whereNull('group_id')->orderBy('name')->get();

        foreach ($childLedgers as $ledger) {
            $ledgerData = $this->processLedger($ledger);

            // Update totals
            $tree = $this->updateTotals($tree, $ledgerData);

            $tree['children_ledgers'][] = $ledgerData;
        }
    }
    protected function processLedger($ledger)
    {
        $ledgerData = [
            'id'                => $ledger->id,
            'name'              => $ledger->name,
            'code'              => $ledger->code,
            'l_group_id'        => $ledger->group_id,
            'l_type'            => $ledger->type,
            'l_reconciliation'  => $ledger->reconciliation,
            'l_notes'           => $ledger->notes,
            'op_total'          => $this->startDate ? 0.00 : (float) $ledger->op_balance,
            'op_total_dc'       => $ledger->op_balance_dc,
        ];

        if ($this->onlyOpening) {
            $ledgerData['dr_total'] = 0.00;
            $ledgerData['cr_total'] = 0.00;
            $ledgerData['cl_total'] = $ledgerData['op_total'];
            $ledgerData['cl_total_dc'] = $ledgerData['op_total_dc'];
        } else {
            $closingBalance = $ledger->getClosingBalance($this->startDate, $this->endDate);

            $ledgerData['dr_total'] = $closingBalance['dr_total'];
            $ledgerData['cr_total'] = $closingBalance['cr_total'];
            $ledgerData['cl_total'] = $closingBalance['amount'];
            $ledgerData['cl_total_dc'] = $closingBalance['dc'];
        }

        return $ledgerData;
    }

    protected function updateTotals($tree, $data)
    {
        // Ensure required keys are set in $data with default values
        $data = array_merge([
            'op_total'      => 0.00,
            'op_total_dc'   => 'D',
            'cl_total'      => 0.00,
            'cl_total_dc'   => 'D',
            'dr_total'      => 0.00,
            'cr_total'      => 0.00,
        ], $data);

        // Update opening balance
        $op = AccountingHelper::calculateWithDc($tree['op_total'], $tree['op_total_dc'], $data['op_total'], $data['op_total_dc']);
        $tree['op_total'] = $op['amount'];
        $tree['op_total_dc'] = $op['dc'];

        // Update closing balance
        $cl = AccountingHelper::calculateWithDc($tree['cl_total'], $tree['cl_total_dc'], $data['cl_total'], $data['cl_total_dc']);
        $tree['cl_total'] = $cl['amount'];
        $tree['cl_total_dc'] = $cl['dc'];

        // Update Dr and Cr totals
        $tree['dr_total'] += $data['dr_total'];
        $tree['cr_total'] += $data['cr_total'];

        return $tree;
    }
}
