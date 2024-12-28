<?php
namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TagTreeService;
use App\Repositories\TagRepository;
use Illuminate\Http\Request;

class TagController extends Controller
{
    protected $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function index()
    {
        return response()->json(Tag::all(), 200);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'background' => 'nullable|string|max:255',
        ]);

        $this->tagRepository->create($data);
        return response()->json(['message' => 'Tag created successfully']);
    }

    public function show($id)
    {
        $tag = $this->tagRepository->findById($id);
        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'background' => 'nullable|string|max:255',
        ]);

        $this->tagRepository->update($id, $data);
        return response()->json(['message' => 'Tag updated successfully']);
    }

    public function destroy($id)
    {
        try {
            $this->tagRepository->delete($id);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 400);
        }

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}
