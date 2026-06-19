<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use Illuminate\Pagination\LengthAwarePaginator;

class SectionController extends Controller
{
    public function index()
    {
        /** @var LengthAwarePaginator $sections */
        $sections = Section::with([
            'challenges' => function ($query) {
                $query->orderBy('id');
            },
        ])->withCount('challenges')->orderBy('order', 'asc')->paginate(10);
        $sections->withQueryString();
        return view('lecturer.sections.index', compact('sections'));
    }

    public function create()
    {
        return view('lecturer.sections.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer|unique:sections,order',
            'name' => 'required|string|max:255|unique:sections,name',
        ], [], [
            'order' => 'urutan',
            'name' => 'nama bagian',
        ]);

        Section::create([
            'order' => $request->order,
            'name' => trim($request->name),
        ]);

        return redirect()->route('lecturer.sections.index')->with('success', 'Bagian belajar berhasil dibuat.');
    }

    public function edit(Section $section)
    {
        return view('lecturer.sections.edit', compact('section'));
    }

    public function show(Section $section)
    {
        return redirect()->route('lecturer.sections.edit', $section);
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'order' => 'required|integer|unique:sections,order,' . $section->id,
            'name' => 'required|string|max:255|unique:sections,name,' . $section->id,
        ], [], [
            'order' => 'urutan',
            'name' => 'nama bagian',
        ]);

        $section->update([
            'order' => $request->order,
            'name' => trim($request->name),
        ]);

        return redirect()->route('lecturer.sections.index')->with('success', 'Bagian belajar berhasil diperbarui.');
    }

    public function destroy(Section $section)
    {
        try {
            $deletedName = $section->name;
            $section->delete();
            return redirect()->route('lecturer.sections.index')->with([
                'success' => "Bagian belajar {$deletedName} berhasil dihapus.",
            ]);
        } catch (\Exception $e) {
            return redirect()->route('lecturer.sections.index')->with('error', 'Bagian belajar tidak bisa dihapus. Pastikan tidak ada mission yang masih terhubung.');
        }
    }
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orderedIds' => 'required|array',
            'orderedIds.*' => 'integer|exists:sections,id',
        ]);

        $orderedIds = $validated['orderedIds'];

        foreach ($orderedIds as $index => $id) {
            Section::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['message' => 'Urutan bagian belajar berhasil diperbarui.']);
    }
}
