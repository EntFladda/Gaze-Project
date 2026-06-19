@extends('lecturer.layouts.app')

@section('content')
    <div class="question-form-page">
        <section class="question-form-hero">
            <p class="question-form-kicker">Perbarui Soal</p>
            <h1 class="question-form-title">Edit soal untuk mission</h1>
            <p class="question-form-copy">
                Perbaiki teks pertanyaan, bantuan, pembahasan, Poin, atau jawaban agar kualitas belajar mahasiswa tetap terjaga.
            </p>
        </section>

        @if ($errors->any())
            <div class="question-form-alert">
                <strong>Data belum bisa diperbarui.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="question-form" action="{{ route('lecturer.questions.update', $question->id) }}" method="POST"
            enctype="multipart/form-data" class="question-form-card bg-white">
            @csrf
            @method('PUT')

            <div class="question-form-grid two">
                <div>
                    @php
                        $selectedMissionId = old('challenge_id', $question->challenge_id);
                        $selectedMission = $challenges->firstWhere('id', (int) $selectedMissionId);
                    @endphp
                    <label for="mission-search" class="question-form-label">Misi</label>
                    <div class="question-mission-picker">
                        <input type="hidden" name="challenge_id" id="challenge_id" value="{{ $selectedMissionId }}" required>
                        <button type="button" id="mission-picker-button" class="question-form-input question-mission-button"
                            onclick="toggleMissionPicker()">
                            <span id="mission-picker-label">
                                {{ $selectedMission ? (($selectedMission->section?->order ?? '-') . '. ' . $selectedMission->title) : 'Pilih misi' }}
                            </span>
                            <span>v</span>
                        </button>
                        <div id="mission-picker-menu" class="question-mission-menu hidden">
                            <input type="text" id="mission-search" class="question-mission-search"
                                placeholder="Cari mission atau bagian materi..." oninput="filterMissionOptions()">
                            <div class="question-mission-options">
                                @foreach ($challenges as $challenge)
                                    @php
                                        $missionLabel = ($challenge->section?->order ?? '-') . '. ' . $challenge->title;
                                    @endphp
                                    <button type="button" class="question-mission-option"
                                        data-value="{{ $challenge->id }}"
                                        data-label="{{ $missionLabel }}"
                                        onclick="selectMission(this)">
                                        {{ $missionLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="type_display" class="question-form-label">Tipe Soal</label>
                    <select name="type_display" id="type_display" class="question-form-input" disabled>
                        <option value="multiple_choice" {{ $question->type == 'multiple_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                        <option value="true_false" {{ $question->type == 'true_false' ? 'selected' : '' }}>Benar / Salah</option>
                        <option value="essay" {{ $question->type == 'essay' ? 'selected' : '' }}>Esai</option>
                    </select>
                    <input type="hidden" name="type" value="{{ $question->type }}">
                </div>
            </div>

            <input type="hidden" name="description" value="{{ old('description', $question->description) }}">

            <div class="question-block-panel">
                <div class="question-answer-head">
                    <div>
                        <p class="question-answer-kicker">Susunan Soal</p>
                        <h3 class="question-answer-title">Susun isi soal fleksibel</h3>
                    </div>
                    <div class="question-block-actions">
                        <button type="button" onclick="addBlock('text')" class="question-form-btn accent">Tambah Teks</button>
                        <button type="button" onclick="addBlock('image')" class="question-form-btn accent secondary">Tambah Gambar</button>
                    </div>
                </div>
                <p class="question-block-copy">Semua isi tampil berurutan di halaman mahasiswa. Gunakan tombol naik/turun untuk mengatur posisinya.</p>
                <div id="blocks-body" class="question-block-list">
                    @php
                        $initialBlocks = $question->blocks->values();
                        if ($initialBlocks->isEmpty()) {
                            $legacyBlocks = collect();

                            if ($question->question_image) {
                                $legacyBlocks->push((object) [
                                    'type' => 'image',
                                    'content' => null,
                                    'image_path' => $question->question_image,
                                ]);
                            }

                            if (filled($question->description)) {
                                $legacyBlocks->push((object) [
                                    'type' => 'text',
                                    'content' => $question->description,
                                    'image_path' => null,
                                ]);
                            }

                            $initialBlocks = $legacyBlocks;
                        }
                    @endphp

                    @foreach ($initialBlocks as $index => $block)
                        <div class="question-block-card" data-block-index="{{ $index }}">
                            <div class="question-block-card-head">
                                <span class="question-block-badge">{{ $block->type === 'text' ? 'Teks' : 'Gambar' }}</span>
                                <div class="question-block-card-tools">
                                    <button type="button" class="question-form-btn question-block-order-btn" onclick="moveBlock(this, -1)">Naik</button>
                                    <button type="button" class="question-form-btn question-block-order-btn" onclick="moveBlock(this, 1)">Turun</button>
                                    <button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeBlock(this)">Hapus</button>
                                </div>
                            </div>
                            <input type="hidden" name="blocks[{{ $index }}][type]" value="{{ $block->type }}">
                            @if ($block->type === 'text')
                                <div class="question-block-field">
                                    <textarea name="blocks[{{ $index }}][content]" rows="4" placeholder="Tulis deskripsi, konteks, petunjuk, atau paragraf soal di bagian ini...">{{ $block->content }}</textarea>
                                </div>
                            @else
                                <input type="hidden" name="old_block_images[{{ $index }}]" value="{{ $block->image_path }}">
                                <div class="question-block-field">
                                    @if ($block->image_path)
                                        <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar susunan soal" class="question-current-image">
                                    @endif
                                    <input type="file" name="block_images[{{ $index }}]" accept="image/*" class="mt-3">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="question-form-grid one">
                <div>
                    <label for="question_text" class="question-form-label">Teks Pertanyaan</label>
                    <textarea name="question_text" id="question_text" rows="4" class="question-form-input" required>{{ old('question_text', $question->question_text) }}</textarea>
                </div>

                <div>
                    <label for="question_image" class="question-form-label">Gambar Soal (Opsional)</label>
                    <div id="question-image-container" class="question-image-stack">
                        @if ($question->question_image)
                            <img src="{{ asset('storage/' . $question->question_image) }}" alt="Gambar soal" class="question-current-image">
                            <button type="button" class="question-form-btn danger small" onclick="deleteQuestionImage()">Hapus Gambar Soal</button>
                        @else
                            <span class="question-image-empty">Belum ada gambar soal.</span>
                        @endif
                    </div>
                    <input type="file" name="question_image" id="question_image" class="question-form-input file-input mt-3" accept="image/*">
                    <p class="question-form-hint">Tambahkan gambar jika soal membutuhkan ilustrasi, diagram, tabel, atau pola visual.</p>
                </div>
            </div>

            <div class="question-form-grid one">
                <div>
                    <label for="help_text" class="question-form-label">Bantuan Saat Salah</label>
                    <textarea name="help_text" id="help_text" rows="4" class="question-form-input"
                        placeholder="Tuliskan petunjuk langkah pengerjaan tanpa langsung memberi jawaban.">{{ old('help_text', $question->help_text) }}</textarea>
                </div>
            </div>

            <div class="question-block-panel explanation-panel">
                <div class="question-answer-head">
                    <div>
                        <p class="question-answer-kicker">Pembahasan Akhir</p>
                        <h3 class="question-answer-title">Susun pembahasan fleksibel</h3>
                    </div>
                    <div class="question-block-actions">
                        <button type="button" onclick="addExplanationBlock('text')" class="question-form-btn accent">Tambah Teks</button>
                        <button type="button" onclick="addExplanationBlock('image')" class="question-form-btn accent secondary">Tambah Gambar</button>
                    </div>
                </div>
                <p class="question-block-copy">Semua pembahasan tampil berurutan di halaman review mahasiswa. Gunakan tombol naik/turun untuk mengatur posisi teks dan gambar.</p>
                <div id="explanation-blocks-body" class="question-block-list">
                    @php
                        $initialExplanationBlocks = $question->explanationBlocks->values();
                        if ($initialExplanationBlocks->isEmpty()) {
                            $legacyExplanationBlocks = collect();
                            if (filled($question->explanation_text)) {
                                $legacyExplanationBlocks->push((object) [
                                    'type' => 'text',
                                    'content' => $question->explanation_text,
                                    'image_path' => null,
                                ]);
                            }
                            foreach ($question->explanationImages as $image) {
                                $legacyExplanationBlocks->push((object) [
                                    'type' => 'image',
                                    'content' => null,
                                    'image_path' => $image->image_path,
                                ]);
                            }
                            if ($legacyExplanationBlocks->isEmpty() && $question->explanation_image) {
                                $legacyExplanationBlocks->push((object) [
                                    'type' => 'image',
                                    'content' => null,
                                    'image_path' => $question->explanation_image,
                                ]);
                            }
                            $initialExplanationBlocks = $legacyExplanationBlocks;
                        }
                    @endphp

                    @foreach ($initialExplanationBlocks as $index => $block)
                        <div class="question-block-card explanation-block-card" data-explanation-index="{{ $index }}">
                            <div class="question-block-card-head">
                                <span class="question-block-badge">{{ $block->type === 'text' ? 'Teks Pembahasan' : 'Gambar Pembahasan' }}</span>
                                <div class="question-block-card-tools">
                                    <button type="button" class="question-form-btn question-block-order-btn" onclick="moveExplanationBlock(this, -1)">Naik</button>
                                    <button type="button" class="question-form-btn question-block-order-btn" onclick="moveExplanationBlock(this, 1)">Turun</button>
                                    <button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeExplanationBlock(this)">Hapus</button>
                                </div>
                            </div>
                            <input type="hidden" name="explanation_blocks[{{ $index }}][type]" value="{{ $block->type }}">
                            @if ($block->type === 'text')
                                <div class="question-block-field">
                                    <textarea name="explanation_blocks[{{ $index }}][content]" rows="4" placeholder="Tulis paragraf pembahasan di bagian ini...">{{ $block->content }}</textarea>
                                </div>
                            @else
                                <input type="hidden" name="old_explanation_block_images[{{ $index }}]" value="{{ $block->image_path }}">
                                <div class="question-block-field">
                                    @if ($block->image_path)
                                        <img src="{{ asset('storage/' . $block->image_path) }}" alt="Gambar pembahasan" class="question-current-image">
                                    @endif
                                    <input type="file" name="explanation_block_images[{{ $index }}]" accept="image/*" class="mt-3">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="question-form-grid two">
                <div>
                    <label for="score" class="question-form-label">Poin</label>
                    <input type="number" name="score" id="score" class="question-form-input" value="{{ old('score', $question->score) }}" required>
                </div>

                <div>
                    <label for="exp" class="question-form-label">EXP</label>
                    <input type="number" name="exp" id="exp" class="question-form-input" value="{{ old('exp', $question->exp) }}" required>
                </div>
            </div>

            <div class="question-answer-panel">
                <div class="question-answer-head">
                    <div>
                        <p class="question-answer-kicker">Jawaban</p>
                        <h3 class="question-answer-title">Kelola kunci jawaban</h3>
                    </div>
                    @if ($question->type === 'multiple_choice')
                        <button type="button" id="add-answer-btn" onclick="addAnswer()" class="question-form-btn accent">Tambah Jawaban</button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="question-answer-table">
                        <thead>
                            <tr>
                                <th>Jawaban</th>
                                <th>Gambar</th>
                                <th>Kunci</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="answers-body">
                            @if ($question->type === 'multiple_choice')
                                @foreach ($question->answers as $index => $answer)
                                    <tr class="answer-row">
                                        <td><input type="text" name="answers[]" value="{{ $answer->answer }}" required></td>
                                        <td>
                                            <div class="question-answer-image-stack" id="answer-image-container-{{ $index }}">
                                                @if ($answer->answer_image)
                                                    <img src="{{ asset('storage/' . $answer->answer_image) }}" alt="Gambar jawaban" class="question-answer-image">
                                                @else
                                                    <span class="question-image-empty compact">Belum ada gambar.</span>
                                                @endif
                                                <input type="file" name="answer_images[]" accept="image/*">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="is_correct[]" value="{{ $answer->is_correct ? '1' : '0' }}" class="is-correct-hidden">
                                            <label class="correct-checkbox-wrap">
                                                <input type="checkbox" value="1" class="correct-checkbox" {{ $answer->is_correct ? 'checked' : '' }} onchange="toggleCorrectAnswer(this)">
                                                <span>Pilih</span>
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeAnswer(this)">Hapus</button>
                                        </td>
                                        <input type="hidden" name="old_answer_images[{{ $index }}]" value="{{ $answer->answer_image }}">
                                    </tr>
                                @endforeach
                            @elseif ($question->type === 'true_false')
                                <tr>
                                    <td class="font-semibold">True</td>
                                    <td class="text-slate-500">-</td>
                                    <td class="text-center">
                                        <input type="radio" name="correct_answer" value="true" {{ $question->answers->where('answer', 'True')->first()?->is_correct ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center text-slate-500">-</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold">False</td>
                                    <td class="text-slate-500">-</td>
                                    <td class="text-center">
                                        <input type="radio" name="correct_answer" value="false" {{ $question->answers->where('answer', 'False')->first()?->is_correct ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center text-slate-500">-</td>
                                </tr>
                            @elseif ($question->type === 'essay')
                                <tr>
                                    <td colspan="2">
                                        <textarea name="answers[]" rows="4" class="question-form-input" required
                                            placeholder="Tulis kunci jawaban esai yang dianggap benar.">{{ $question->answers->first()->answer ?? '' }}</textarea>
                                    </td>
                                    <td class="text-center text-slate-500">Kunci esai</td>
                                    <td class="text-center text-slate-500">-</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="question-form-actions">
                <a href="{{ route('lecturer.questions.index') }}" class="question-form-btn neutral">Kembali</a>
                <button type="submit" id="submit-btn" class="question-form-btn primary">Perbarui Soal</button>
            </div>
        </form>

        <div id="saving-overlay" class="question-saving-overlay hidden">
            <div class="question-saving-card">
                <div class="question-saving-spinner"></div>
                <p class="question-saving-title">Menyimpan perubahan...</p>
                <p class="question-saving-copy">Tunggu sebentar, isi soal dan gambar sedang diperbarui.</p>
            </div>
        </div>
    </div>

    <style>
        .question-form-page { max-width: 1100px; margin: 0 auto; }
        .question-form-hero { margin-bottom: 24px; padding: 28px; border-radius: 30px; border: 1px solid rgba(183, 204, 230, 0.14); background: rgba(11, 47, 107, 0.78); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22); }
        .question-form-kicker { margin: 0; font-size: 12px; letter-spacing: 0.34em; text-transform: uppercase; color: rgba(183, 204, 230, 0.75); }
        .question-form-title { margin: 12px 0 0; color: #fff; font-size: 40px; font-weight: 700; }
        .question-form-copy { margin: 14px 0 0; color: rgba(220, 231, 243, 0.76); line-height: 1.8; max-width: 760px; }
        .question-form-alert { margin-bottom: 16px; padding: 16px 18px; border-radius: 18px; background: rgba(254, 226, 226, 0.96); color: #991b1b; }
        .question-form-alert ul { margin: 10px 0 0 18px; }
        .question-form-card { padding: 24px; border-radius: 30px; }
        .question-form-grid { display: grid; gap: 20px; margin-bottom: 20px; }
        .question-form-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .question-form-grid.one { grid-template-columns: 1fr; }
        .question-form-label { display: block; margin-bottom: 10px; color: #263E5C; font-weight: 700; }
        .question-form-input { width: 100%; padding: 14px 16px; border-radius: 16px; border: 1px solid #9CB8D8; box-sizing: border-box; color: #09254A !important; background: #fff !important; }
        .question-form-input::placeholder { color: #94a3b8; }
        .question-form-input option { color: #09254A; background: #fff; }
        .question-form-hint { margin: 8px 0 0; color: #6A7C93; font-size: 13px; line-height: 1.6; }
        .question-mission-picker { position: relative; }
        .question-mission-button { display: flex; align-items: center; justify-content: space-between; gap: 12px; text-align: left; }
        .question-mission-menu { position: absolute; z-index: 50; top: calc(100% + 8px); left: 0; right: 0; padding: 12px; border-radius: 20px; border: 1px solid #9CB8D8; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18); }
        .question-mission-menu.hidden { display: none; }
        .question-mission-search { width: 100%; margin-bottom: 10px; padding: 12px 14px; border-radius: 14px; border: 1px solid #e5e7eb; color: #09254A; background: #fff; box-sizing: border-box; }
        .question-mission-options { max-height: 280px; overflow-y: auto; display: grid; gap: 6px; }
        .question-mission-option { width: 100%; padding: 12px 14px; border: 0; border-radius: 12px; background: #E8F0F8; color: #09254A; text-align: left; cursor: pointer; font-weight: 600; }
        .question-mission-option:hover { background: #DCE7F3; color: #1D5FD6; }
        .file-input { padding: 12px; }
        .mt-3 { margin-top: 12px; }
        .explanation-image-list { display: grid; gap: 12px; }
        .explanation-image-row { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 14px; border: 1px solid #9CB8D8; border-radius: 18px; background: #E8F0F8; }
        .explanation-order-badge { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; background: linear-gradient(135deg, #1D5FD6, #F2A93B); color: #fff; font-weight: 800; }
        .explanation-image-tools { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .question-current-image, .question-answer-image { width: 120px; height: 120px; object-fit: cover; border-radius: 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14); }
        .question-answer-image { width: 84px; height: 84px; border-radius: 16px; }
        .question-image-stack { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; padding: 14px; border: 1px solid #9CB8D8; border-radius: 18px; background: #E8F0F8; }
        .question-image-empty { color: #6A7C93; font-size: 14px; }
        .question-image-empty.compact { display: inline-block; }
        .question-answer-panel { margin-top: 10px; padding: 22px; border-radius: 24px; background: #E8F0F8; border: 1px solid #B7CCE6; }
        .question-block-panel { margin: 0 0 20px; padding: 22px; border-radius: 24px; background: #F4F8FC; border: 1px solid #cfe2ff; }
        .question-block-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .question-block-copy { margin: 0 0 16px; color: #53657A; line-height: 1.7; }
        .question-block-list { display: grid; gap: 16px; }
        .question-block-card { padding: 18px; border-radius: 20px; border: 1px solid #d9e8ff; background: #fff; }
        .question-block-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .question-block-card-tools { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .question-block-badge { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; background: #DCE7F3; color: #1D5FD6; }
        .question-block-order-btn { background: #53657A; padding: 10px 12px; }
        .question-block-field { margin-top: 12px; }
        .question-block-field textarea, .question-block-field input[type="file"] { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #d7dfea; box-sizing: border-box; color: #09254A !important; background: #fff !important; }
        .question-answer-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .question-answer-kicker { margin: 0; font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: #1D5FD6; }
        .question-answer-title { margin: 8px 0 0; color: #09254A; font-size: 24px; font-weight: 700; }
        .question-answer-table { width: 100%; border-collapse: collapse; }
        .question-answer-table th, .question-answer-table td { padding: 12px; border-bottom: 1px solid #B7CCE6; color: #263E5C; vertical-align: top; }
        .question-answer-table thead th { background: #E8F0F8; color: #1D5FD6; font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; }
        .question-answer-image-stack { display: flex; flex-direction: column; gap: 10px; }
        .question-answer-image-stack input[type="file"], .answer-row input[type="text"] { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #B7CCE6; box-sizing: border-box; color: #09254A !important; background: #fff !important; }
        .correct-checkbox-wrap { display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; font-size: 13px; color: #1D5FD6; font-weight: 700; }
        .correct-checkbox { width: 22px; height: 22px; cursor: pointer; appearance: auto !important; -webkit-appearance: auto !important; accent-color: #1D5FD6; }
        .question-form-actions { display: flex; justify-content: space-between; gap: 14px; margin-top: 24px; }
        .question-form-btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 18px; border-radius: 16px; text-decoration: none; font-weight: 700; border: 0; cursor: pointer; color: #fff; }
        .question-form-btn.neutral { background: #6A7C93; }
        .question-form-btn.primary { background: linear-gradient(90deg, #1D5FD6, #2BA7D8); }
        .question-form-btn.accent { background: #2BA7D8; }
        .question-form-btn.accent.secondary { background: #1D5FD6; }
        .question-form-btn.danger { background: #ef4444; }
        .question-form-btn.small { padding: 10px 14px; border-radius: 14px; }
        .question-saving-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .question-saving-overlay.hidden { display: none; }
        .question-saving-card { width: min(360px, calc(100vw - 32px)); padding: 24px; border-radius: 24px; background: #fff; text-align: center; box-shadow: 0 30px 80px rgba(15, 23, 42, 0.26); }
        .question-saving-spinner { width: 42px; height: 42px; margin: 0 auto 14px; border-radius: 999px; border: 4px solid #DCE7F3; border-top-color: #1D5FD6; animation: spin 0.8s linear infinite; }
        .question-saving-title { margin: 0; color: #09254A; font-size: 18px; font-weight: 700; }
        .question-saving-copy { margin: 8px 0 0; color: #6A7C93; line-height: 1.6; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            .question-form-grid.two { grid-template-columns: 1fr; }
            .question-form-title { font-size: 32px; }
            .question-answer-head, .question-form-actions { flex-direction: column; align-items: stretch; }
        }
    </style>

    <script>
        const blocksBody = document.getElementById('blocks-body');
        let blockIndex = {{ $initialBlocks->count() }};

        function toggleMissionPicker() {
            const menu = document.getElementById('mission-picker-menu');
            menu.classList.toggle('hidden');

            if (!menu.classList.contains('hidden')) {
                document.getElementById('mission-search').focus();
            }
        }

        function selectMission(option) {
            document.getElementById('challenge_id').value = option.dataset.value;
            document.getElementById('mission-picker-label').textContent = option.dataset.label;
            document.getElementById('mission-picker-menu').classList.add('hidden');
            document.getElementById('mission-search').value = '';
            filterMissionOptions();
        }

        function filterMissionOptions() {
            const search = document.getElementById('mission-search').value.toLowerCase();
            document.querySelectorAll('.question-mission-option').forEach((option) => {
                option.style.display = option.dataset.label.toLowerCase().includes(search) ? '' : 'none';
            });
        }

        document.addEventListener('click', function(event) {
            const picker = document.querySelector('.question-mission-picker');
            if (picker && !picker.contains(event.target)) {
                document.getElementById('mission-picker-menu').classList.add('hidden');
            }
        });

        function addAnswer() {
            const newRow = `<tr class="answer-row"><td><input type="text" name="answers[]" required></td><td><div class="question-answer-image-stack"><span class="question-image-empty compact">Belum ada gambar.</span><input type="file" name="answer_images[]" accept="image/*"></div></td><td class="text-center"><input type="hidden" name="is_correct[]" value="0" class="is-correct-hidden"><label class="correct-checkbox-wrap"><input type="checkbox" class="correct-checkbox" onchange="toggleCorrectAnswer(this)"><span>Pilih</span></label></td><td class="text-center"><button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeAnswer(this)">Hapus</button></td></tr>`;
            document.getElementById('answers-body').insertAdjacentHTML('beforeend', newRow);
        }

        function toggleCorrectAnswer(checkbox) {
            const hiddenInput = checkbox.closest('tr').querySelector('.is-correct-hidden');
            if (hiddenInput) hiddenInput.value = checkbox.checked ? '1' : '0';
        }

        function removeAnswer(button) { button.closest('tr').remove(); }

        function deleteQuestionImage() {
            document.getElementById('question-image-container').innerHTML = "<span class='question-image-empty'>Belum ada gambar soal.</span>";
            let deleteInput = document.getElementById('delete-question-image');
            if (!deleteInput) {
                deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_question_image';
                deleteInput.id = 'delete-question-image';
                document.getElementById('question-form').appendChild(deleteInput);
            }
            deleteInput.value = '1';
        }

        let explanationBlockIndex = {{ $initialExplanationBlocks->count() }};

        function addExplanationBlock(type, content = '') {
            const currentIndex = explanationBlockIndex++;
            const isText = type === 'text';
            const card = `
                <div class="question-block-card explanation-block-card" data-explanation-index="${currentIndex}">
                    <div class="question-block-card-head">
                        <span class="question-block-badge">${isText ? 'Teks Pembahasan' : 'Gambar Pembahasan'}</span>
                        <div class="question-block-card-tools">
                            <button type="button" class="question-form-btn question-block-order-btn" onclick="moveExplanationBlock(this, -1)">Naik</button>
                            <button type="button" class="question-form-btn question-block-order-btn" onclick="moveExplanationBlock(this, 1)">Turun</button>
                            <button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeExplanationBlock(this)">Hapus</button>
                        </div>
                    </div>
                    <input type="hidden" name="explanation_blocks[${currentIndex}][type]" value="${type}">
                    ${isText
                        ? `<div class="question-block-field"><textarea name="explanation_blocks[${currentIndex}][content]" rows="4" placeholder="Tulis paragraf pembahasan di bagian ini...">${content}</textarea></div>`
                        : `<div class="question-block-field"><input type="file" name="explanation_block_images[${currentIndex}]" accept="image/*"></div>`
                    }
                </div>
            `;
            document.getElementById('explanation-blocks-body').insertAdjacentHTML('beforeend', card);
            reindexExplanationBlocks();
        }

        function removeExplanationBlock(button) {
            button.closest('.explanation-block-card').remove();
            reindexExplanationBlocks();
        }

        function moveExplanationBlock(button, direction) {
            const card = button.closest('.explanation-block-card');
            const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
            if (!sibling) return;

            if (direction < 0) {
                card.parentElement.insertBefore(card, sibling);
            } else {
                card.parentElement.insertBefore(sibling, card);
            }

            reindexExplanationBlocks();
        }

        function reindexExplanationBlocks() {
            document.querySelectorAll('.explanation-block-card').forEach((card, index) => {
                card.dataset.explanationIndex = index;
                card.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name
                        .replace(/explanation_blocks\[\d+\]/, `explanation_blocks[${index}]`)
                        .replace(/explanation_block_images\[\d+\]/, `explanation_block_images[${index}]`)
                        .replace(/old_explanation_block_images\[\d+\]/, `old_explanation_block_images[${index}]`);
                });
            });
            explanationBlockIndex = document.querySelectorAll('.explanation-block-card').length;
        }

        function addBlock(type, content = '') {
            const currentIndex = blockIndex++;
            const isText = type === 'text';
            const card = `
                <div class="question-block-card" data-block-index="${currentIndex}">
                    <div class="question-block-card-head">
                        <span class="question-block-badge">${isText ? 'Teks' : 'Gambar'}</span>
                        <div class="question-block-card-tools">
                            <button type="button" class="question-form-btn question-block-order-btn" onclick="moveBlock(this, -1)">Naik</button>
                            <button type="button" class="question-form-btn question-block-order-btn" onclick="moveBlock(this, 1)">Turun</button>
                            <button type="button" class="question-form-btn" style="background:#ef4444;padding:10px 14px;" onclick="removeBlock(this)">Hapus</button>
                        </div>
                    </div>
                    <input type="hidden" name="blocks[${currentIndex}][type]" value="${type}">
                    ${isText
                        ? `<div class="question-block-field"><textarea name="blocks[${currentIndex}][content]" rows="4" placeholder="Tulis deskripsi, konteks, petunjuk, atau paragraf soal di bagian ini...">${content}</textarea></div>`
                        : `<div class="question-block-field"><input type="file" name="block_images[${currentIndex}]" accept="image/*"></div>`
                    }
                </div>
            `;
            blocksBody.insertAdjacentHTML('beforeend', card);
            reindexBlocks();
        }

        function removeBlock(button) {
            button.closest('.question-block-card').remove();
            reindexBlocks();
        }

        function moveBlock(button, direction) {
            const card = button.closest('.question-block-card');
            const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;

            if (!sibling) {
                return;
            }

            if (direction < 0) {
                blocksBody.insertBefore(card, sibling);
            } else {
                blocksBody.insertBefore(sibling, card);
            }

            reindexBlocks();
        }

        function reindexBlocks() {
            document.querySelectorAll('.question-block-card').forEach((card, index) => {
                card.dataset.blockIndex = index;
                card.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name
                        .replace(/blocks\[\d+\]/, `blocks[${index}]`)
                        .replace(/block_images\[\d+\]/, `block_images[${index}]`)
                        .replace(/old_block_images\[\d+\]/, `old_block_images[${index}]`);
                });
            });
            blockIndex = document.querySelectorAll('.question-block-card').length;
        }

        function showSavingState(message = 'Menyimpan soal...') {
            reindexBlocks();
            reindexExplanationBlocks();
            const submitButton = document.getElementById('submit-btn');
            const overlay = document.getElementById('saving-overlay');
            const savingTitle = overlay.querySelector('.question-saving-title');
            const savingCopy = overlay.querySelector('.question-saving-copy');

            submitButton.disabled = true;
            submitButton.textContent = message;
            savingTitle.textContent = message;
            savingCopy.textContent = 'Mohon tunggu sebentar, data soal sedang disimpan.';
            overlay.classList.remove('hidden');
        }

        const imageCompressionTasks = new WeakMap();
        const compressedImageFiles = new WeakMap();

        async function compressImageInput(input) {
            const file = input.files?.[0];
            if (!file || !file.type.startsWith('image/') || file.type.includes('svg') || file.type.includes('gif')) {
                return;
            }

            const maxSide = 1000;
            const maxUploadSize = 700 * 1024;
            const targetQuality = 0.68;
            const image = new Image();
            const imageUrl = URL.createObjectURL(file);

            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error(`Gambar "${file.name}" tidak bisa dibaca browser untuk dikompres.`));
                image.src = imageUrl;
            });

            const scale = Math.min(1, maxSide / Math.max(image.width, image.height));
            if (scale === 1 && file.size <= maxUploadSize) {
                URL.revokeObjectURL(imageUrl);
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.width * scale));
            canvas.height = Math.max(1, Math.round(image.height * scale));
            canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(imageUrl);

            let quality = targetQuality;
            let blob = null;
            do {
                blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
                quality -= 0.08;
            } while (blob && blob.size > maxUploadSize && quality >= 0.34);

            if (blob && blob.size > maxUploadSize) {
                const shrinkScale = Math.max(0.35, Math.sqrt(maxUploadSize / blob.size) * 0.88);
                const smallerCanvas = document.createElement('canvas');
                smallerCanvas.width = Math.max(1, Math.round(canvas.width * shrinkScale));
                smallerCanvas.height = Math.max(1, Math.round(canvas.height * shrinkScale));
                smallerCanvas.getContext('2d').drawImage(canvas, 0, 0, smallerCanvas.width, smallerCanvas.height);
                blob = await new Promise((resolve) => smallerCanvas.toBlob(resolve, 'image/jpeg', 0.48));
            }

            if (!blob) {
                throw new Error(`Gambar "${file.name}" gagal dikompres.`);
            }

            if (blob.size >= file.size && file.size > maxUploadSize) {
                throw new Error(`Gambar "${file.name}" masih terlalu besar setelah kompresi.`);
            }

            if (blob.size >= file.size) {
                return;
            }

            const dataTransfer = new DataTransfer();
            const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
            dataTransfer.items.add(compressedFile);
            input.files = dataTransfer.files;
        }

        function queueImageCompression(input) {
            if (!input.files?.length) {
                imageCompressionTasks.delete(input);
                compressedImageFiles.delete(input);
                return Promise.resolve();
            }

            const selectedFile = input.files[0];
            if (compressedImageFiles.get(input) === selectedFile) {
                return Promise.resolve();
            }

            const task = compressImageInput(input)
                .finally(() => {
                    compressedImageFiles.set(input, input.files?.[0] || selectedFile);
                    if (imageCompressionTasks.get(input) === task) {
                        imageCompressionTasks.delete(input);
                    }
                });

            imageCompressionTasks.set(input, task);
            return task;
        }

        async function compressFormImages(form) {
            const imageInputs = [...form.querySelectorAll('input[type="file"][accept*="image"]')];
            await Promise.all(imageInputs.map((input) => imageCompressionTasks.get(input) || queueImageCompression(input)));
        }

        function selectedImageUploadSize(form) {
            return [...form.querySelectorAll('input[type="file"][accept*="image"]')]
                .reduce((total, input) => total + (input.files?.[0]?.size || 0), 0);
        }

        function hasSelectedImages(form) {
            return [...form.querySelectorAll('input[type="file"][accept*="image"]')]
                .some((input) => input.files && input.files.length > 0);
        }

        function resetSavingState() {
            const submitButton = document.getElementById('submit-btn');
            const overlay = document.getElementById('saving-overlay');
            submitButton.disabled = false;
            submitButton.textContent = 'Perbarui Soal';
            overlay.classList.add('hidden');
        }

        function showSubmitError(messages) {
            resetSavingState();
            const alertBox = document.querySelector('.question-form-alert') || document.createElement('div');
            alertBox.className = 'question-form-alert';
            alertBox.innerHTML = `<strong>Data belum bisa diperbarui.</strong><ul>${messages.map((message) => `<li>${message}</li>`).join('')}</ul>`;
            document.getElementById('question-form').before(alertBox);
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        document.getElementById('question-form').addEventListener('change', function (event) {
            if (event.target.matches('input[type="file"][accept*="image"]')) {
                queueImageCompression(event.target);
            }
        });

        document.getElementById('question-form').addEventListener('submit', async function (event) {
            reindexBlocks();
            reindexExplanationBlocks();
            if (!this.checkValidity()) {
                return;
            }

            event.preventDefault();
            document.querySelector('.question-form-alert')?.remove();
            showSavingState(hasSelectedImages(this) ? 'Menyiapkan gambar...' : 'Menyimpan soal...');

            if (hasSelectedImages(this)) {
                try {
                    await compressFormImages(this);
                } catch (error) {
                    showSubmitError([error.message || 'Ada gambar yang gagal dikompres otomatis.']);
                    return;
                }
            }

            const totalImageBytes = selectedImageUploadSize(this);
            const maxSafeTotalImageBytes = 6 * 1024 * 1024;
            if (totalImageBytes > maxSafeTotalImageBytes) {
                showSubmitError(['Total gambar masih terlalu besar setelah kompresi. Kurangi jumlah gambar atau pilih gambar yang lebih sederhana.']);
                return;
            }

            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 90000);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const rawResponse = await response.text();
                const data = (() => {
                    try {
                        return JSON.parse(rawResponse);
                    } catch (error) {
                        return {};
                    }
                })();
                if (response.ok && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                const messages = data.errors
                    ? Object.values(data.errors).flat()
                    : [data.message || `Server menolak penyimpanan (HTTP ${response.status}). ${rawResponse ? rawResponse.slice(0, 180) : 'Tidak ada detail tambahan.'}`];
                showSubmitError(messages);
            } catch (error) {
                showSubmitError(['Server belum memberi balasan dalam 90 detik. Perubahan mungkin sudah tersimpan; cek daftar soal dulu sebelum mencoba lagi.']);
            } finally {
                window.clearTimeout(timeout);
            }
        });
    </script>
@endsection


