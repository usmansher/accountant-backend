<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Ledger;
use Illuminate\Support\Facades\Log;

class AccountService
{
    protected $group;
    protected $ledger;

    protected $id = 0;
    protected $name = '';
    protected $code = '';
    protected $gParentId = 0;
    protected $gAffectsGross = -1;

    protected $opTotal = 0;
    protected $opTotalDc = 'D';
    protected $drTotal = 0;
    protected $crTotal = 0;
    protected $clTotal = 0;
    protected $clTotalDc = 'D';

    public $onlyOpening = false;
    public $startDate = null;
    public $endDate = null;
    public $affectsGross = -1;
    public $groupData = [];


    public function __construct(Group $group, Ledger $ledger)
    {
        $this->group = $group;
        $this->ledger = $ledger;
    }

    public function start($id)
    {
        if ($id == 0) {
            $this->group = Group::whereNull('parent_id')->first();
            $this->name = $this->group->name;
        } else {
            $this->group = Group::find($id);

            $this->id = $this->group->id;
            $this->name = $this->group->name;
            $this->code = $this->group->code;
            $this->gParentId = $this->group->parent_id;
            $this->gAffectsGross = $this->group->affects_gross;
        }

        $this->resetTotals();

        if ($this->affectsGross != 1) {
            $this->addSubLedgers();
        }
        $this->addSubGroups();
    }

    protected function resetTotals()
    {
        $this->opTotal = 0;
        $this->opTotalDc = 'D';
        $this->drTotal = 0;
        $this->crTotal = 0;
        $this->clTotal = 0;
        $this->clTotalDc = 'D';
    }

    protected function addSubGroups()
    {
        $query = Group::where('parent_id', $this->id);

        if ($this->affectsGross === 0) {
            $query->where('affects_gross', 0);
        } elseif ($this->affectsGross === 1) {
            $query->where('affects_gross', 1);
        }

        $this->affectsGross = -1;

        $childGroups = $query->orderBy('name')->get();

        foreach ($childGroups as $childGroup) {
            $childAccount = new self($this->group, $this->ledger);
            $childAccount->start($childGroup->id);
            $this->calculateTotals($childAccount);
        }
    }

    protected function addSubLedgers()
    {
        $ledgers = Ledger::where('group_id', $this->id)->orderBy('name')->get();

        foreach ($ledgers as $ledger) {
            $ledger->op_total = $this->startDate ? 0.00 : $ledger->op_balance;
            $ledger->op_total_dc = $ledger->op_balance_dc;

            $this->calculateLedgerTotals($ledger);

            if ($this->onlyOpening) {
                $ledger->dr_total = 0;
                $ledger->cr_total = 0;
                $ledger->cl_total = $ledger->op_total;
                $ledger->cl_total_dc = $ledger->op_total_dc;
            } else {
                $closingBalance = $this->ledger->closingBalance($ledger->id, $this->startDate, $this->endDate);

                $ledger->dr_total = $closingBalance['dr_total'];
                $ledger->cr_total = $closingBalance['cr_total'];
                $ledger->cl_total = $closingBalance['amount'];
                $ledger->cl_total_dc = $closingBalance['dc'];
            }

            $this->updateTotals($ledger);
        }
    }



    protected function calculateLedgerTotals($ledger)
    {
        $this->opTotal = $this->calculateWithDc($this->opTotal, $this->opTotalDc, $ledger->op_total, $ledger->op_total_dc)['amount'];
        $this->opTotalDc = $ledger->op_total_dc;

        $this->clTotal = $this->calculateWithDc($this->clTotal, $this->clTotalDc, $ledger->cl_total, $ledger->cl_total_dc)['amount'];
        $this->clTotalDc = $ledger->cl_total_dc;

        $this->drTotal += $ledger->dr_total;
        $this->crTotal += $ledger->cr_total;
    }

    protected function calculateTotals($childAccount)
    {
        $this->opTotal = $this->calculateWithDc($this->opTotal, $this->opTotalDc, $childAccount->opTotal, $childAccount->opTotalDc)['amount'];
        $this->opTotalDc = $childAccount->opTotalDc;

        $this->clTotal = $this->calculateWithDc($this->clTotal, $this->clTotalDc, $childAccount->clTotal, $childAccount->clTotalDc)['amount'];
        $this->clTotalDc = $childAccount->clTotalDc;

        $this->drTotal += $childAccount->drTotal;
        $this->crTotal += $childAccount->crTotal;
    }

    protected function updateTotals($ledger)
    {
        $this->opTotal = $this->calculateWithDc($this->opTotal, $this->opTotalDc, $ledger->op_total, $ledger->op_total_dc)['amount'];
        $this->opTotalDc = $ledger->op_total_dc;

        $this->clTotal = $this->calculateWithDc($this->clTotal, $this->clTotalDc, $ledger->cl_total, $ledger->cl_total_dc)['amount'];
        $this->clTotalDc = $ledger->cl_total_dc;

        $this->drTotal += $ledger->dr_total;
        $this->crTotal += $ledger->cr_total;
    }

    protected function calculateWithDc($amount1, $dc1, $amount2, $dc2)
    {
        if ($dc1 === $dc2) {
            return ['amount' => $amount1 + $amount2, 'dc' => $dc1];
        } else {
            if ($amount1 > $amount2) {
                return ['amount' => $amount1 - $amount2, 'dc' => $dc1];
            } else {
                return ['amount' => $amount2 - $amount1, 'dc' => $dc2];
            }
        }
    }

    public function getChartOfAccounts()
    {
        // Start with the root level group
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'op_total' => $this->opTotal,
            'op_total_dc' => $this->opTotalDc,
            'cl_total' => $this->clTotal,
            'cl_total_dc' => $this->clTotalDc,
            'subGroups' => [],
            'ledgers' => []
        ];

        $this->addGroupDetails($data);
        return $data;
    }

    protected function addGroupDetails(&$data)
    {
        // Add sub-groups
        $query = Group::where('parent_id', $this->id);

        if ($this->affectsGross === 0) {
            $query->where('affects_gross', 0);
        } elseif ($this->affectsGross === 1) {
            $query->where('affects_gross', 1);
        }

        $childGroups = $query->orderBy('name')->get();
        foreach ($childGroups as $childGroup) {
            $childAccount = new self($this->group, $this->ledger);
            $childAccount->start($childGroup->id);

            $childData = $childAccount->getChartOfAccounts();
            $data['subGroups'][] = $childData;
        }

        // Add ledgers
        $data['ledgers'] = $this->getLedgers();
    }

    protected function getLedgers()
    {
        $ledgers = Ledger::where('group_id', $this->id)->orderBy('name')->get();
        $ledgerData = [];

        foreach ($ledgers as $ledger) {
            $ledgerData[] = [
                'id' => $ledger->id,
                'name' => $ledger->name,
                'code' => $ledger->code,
                'op_total' => $this->startDate ? 0.00 : $ledger->op_balance,
                'op_total_dc' => $ledger->op_balance_dc,
                'dr_total' => $this->onlyOpening ? 0 : $ledger->dr_total,
                'cr_total' => $this->onlyOpening ? 0 : $ledger->cr_total,
                'cl_total' => $this->onlyOpening ? $ledger->op_total : $ledger->cl_total,
                'cl_total_dc' => $this->onlyOpening ? $ledger->op_total_dc : $ledger->cl_total_dc,
            ];
        }

        return $ledgerData;
    }
}
