<?php
namespace App\Repositories;

use App\Models\Tag;
use Illuminate\Validation\ValidationException;

class TagRepository
{
    public function findById($id)
    {
        return Tag::find($id);
    }

    public function create(array $data)
    {
        return Tag::create($data);
    }

    public function update($id, array $data)
    {
        return Tag::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        $tag = Tag::findOrFail($id);


        // Check if the tag has any entries
        if ($tag->entries()->count() > 0) {
            throw ValidationException::withMessages([
                'tag' => 'Cannot delete tag because it has associated entries.'
            ]);
        }

        // Proceed to delete the tag
        $tag->delete();

        return true;

    }
}
