<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\Section;
use Illuminate\Pagination\LengthAwarePaginator;

class ChallengeController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::orderBy('order')->get();

        $sectionSearch = $request->input('section_id');

        /** @var LengthAwarePaginator $challenges */
        $challenges = Challenge::with(['section'])
            ->when($sectionSearch, function ($query, $sectionSearch) {
                return $query->where('section_id', $sectionSearch);
            })
            ->leftJoin('sections', 'challenges.section_id', '=', 'sections.id')
            ->orderBy('sections.order')
            ->orderBy('challenges.id')
            ->select('challenges.*')
            ->withCount('questions')
            ->paginate(5);
        $challenges->withQueryString();

        return view('lecturer.challenges.index', compact('challenges', 'sections', 'sectionSearch'));
    }

    public function create(Request $request)
    {
        $sections = Section::orderBy('order')->get();

        $selectedSectionId = $request->input('section_id');

        return view('lecturer.challenges.create', compact('sections', 'selectedSectionId'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
        ], [], [
            'section_id' => 'bagian materi',
            'title' => 'judul mission',
        ]);

        Challenge::create([
            'section_id' => $request->section_id,
            'title' => trim($request->title),
            'total_exp' => 0,
            'total_score' => 0,
        ]);

        return redirect()->route('lecturer.challenges.index')->with('success', 'Mission berhasil dibuat.');
    }


    public function show(Challenge $challenge)
    {
        return redirect()->route('lecturer.challenges.edit', $challenge);
    }

    public function edit($id)
    {
        $challenge = Challenge::findOrFail($id);
        $sections = Section::orderBy('order', 'asc')->get();
        return view('lecturer.challenges.edit', compact('challenge', 'sections'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
        ], [], [
            'section_id' => 'bagian materi',
            'title' => 'judul mission',
        ]);

        $challenge = Challenge::findOrFail($id);
        $challenge->update([
            'section_id' => $request->section_id,
            'title' => trim($request->title),
        ]);

        return redirect()->route('lecturer.challenges.index')->with('success', 'Mission berhasil diperbarui.');
    }


    public function destroy($id)
    {
        try {
            $challenge = Challenge::findOrFail($id);
            $title = $challenge->title;
            $challenge->delete();

            return redirect()->route('lecturer.challenges.index')
                ->with('success', "Mission {$title} berhasil dihapus.");
        } catch (\Exception $e) {
            return redirect()->route('lecturer.challenges.index')
                ->with('error', 'Mission tidak bisa dihapus. Pastikan tidak ada soal atau hasil pengerjaan yang masih terhubung.');
        }
    }
}
