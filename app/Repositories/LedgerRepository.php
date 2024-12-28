<?php
namespace App\Repositories;

use App\Models\Ledger;
use Illuminate\Validation\ValidationException;

class LedgerRepository
{
    public function findById($id)
    {
        return Ledger::find($id);
    }

    public function create(array $data)
    {
        return Ledger::create($data);
    }

    public function update($id, array $data)
    {
        return Ledger::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $ledger = Ledger::findOrFail($id);


        // Check if the ledger has any entries
        if ($ledger->entries()->count() > 0) {
            throw ValidationException::withMessages([
                'ledger' => 'Cannot delete ledger because it has associated entries.'
            ]);
        }

        // Proceed to delete the ledger
        $ledger->delete();

        return true;

    }
}
