<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Student;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use League\Csv\Reader;
use ZipArchive;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $studentStats = [
            'total' => Student::count(),
            'classes' => Student::whereNotNull('class')->distinct('class')->count('class'),
            'semesters' => Student::whereNotNull('semester')->distinct('semester')->count('semester'),
        ];

        $query = Student::with(['user', 'ranks'])
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.*', 'users.name as user_name', 'users.email as user_email');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('students.nim', 'like', "%{$search}%")
                    ->orWhere('students.prodi', 'like', "%{$search}%")
                    ->orWhere('students.class', 'like', "%{$search}%")
                    ->orWhere('students.semester', 'like', "%{$search}%")
                    ->orWhere('students.phone_number', 'like', "%{$search}%")
                    ->orWhere('students.gender', 'like', "%{$search}%")
                    ->orWhere('students.religion', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'students.created_at');
        $sortOrder = $request->get('order', 'desc');
        $allowedFields = ['user_name', 'user_email', 'prodi', 'class', 'semester', 'students.created_at'];

        if (in_array($sortField, $allowedFields, true)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = $request->get('perPage', 10);

        if ($perPage === 'all') {
            $students = $query->get();
            $pagination = false;
        } else {
            /** @var LengthAwarePaginator $students */
            $students = $query->paginate((int) $perPage);
            $students->withQueryString();
            $pagination = true;
        }

        return view('admin.students.index', compact('students', 'sortField', 'sortOrder', 'perPage', 'pagination', 'studentStats'));
    }

    public function show(Student $student)
    {
        $student->load(['user', 'ranks', 'currentChallenge', 'currentSection']);

        return view('admin.students.show', compact('student'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:6|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'nim' => 'required|string|unique:students,nim',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'religion' => 'required|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'phone_number' => 'nullable|string|max:15',
            'prodi' => 'required|string|in:Sistem Informasi Bisnis,Teknik Informatika',
            'semester' => 'required|integer|min:1|max:8',
            'class' => 'required|string|max:50',
        ]);

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user = User::create([
            'name' => trim($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->filled('password') ? $request->password : $request->nim),
            'profile_photo' => $profilePhotoPath,
        ]);

        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => $request->nim,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'religion' => $request->religion,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'class' => $request->class,
            'streak' => 0,
            'exp' => 0,
            'weekly_score' => 0,
            'total_score' => 0,
        ]);

        $student->load('ranks');
        $student->updateRank();

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'student_file' => ['required', 'file', 'extensions:csv,txt,xlsx', 'max:4096'],
        ]);

        $file = $request->file('student_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $records = $extension === 'xlsx'
            ? $this->readXlsxRecords($file->getRealPath())
            : tap(Reader::createFromPath($file->getRealPath(), 'r'), fn ($csv) => $csv->setHeaderOffset(0))->getRecords();

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($records as $index => $row) {
            $rowNumber = $index + 2;
            $normalized = $this->normalizeImportRow($row);

            $name = trim((string) ($normalized['nama'] ?? $normalized['name'] ?? ''));
            $nim = trim((string) ($normalized['nim'] ?? ''));
            $email = trim((string) ($normalized['email'] ?? ''));
            $class = trim((string) ($normalized['kelas'] ?? $normalized['class'] ?? '1B'));
            $semester = trim((string) ($normalized['semester'] ?? '1'));
            $phone = trim((string) ($normalized['no_telepon'] ?? $normalized['phone_number'] ?? $normalized['telepon'] ?? ''));
            $gender = trim((string) ($normalized['jenis_kelamin'] ?? $normalized['gender'] ?? ''));
            $address = trim((string) ($normalized['alamat'] ?? $normalized['address'] ?? ''));
            $religion = trim((string) ($normalized['agama'] ?? $normalized['religion'] ?? ''));
            $prodi = trim((string) ($normalized['prodi'] ?? 'Sistem Informasi Bisnis'));

            if ($email === '' && $name !== '' && $nim !== '') {
                $email = $this->generateStudentEmail($name, $nim);
            }

            $existingEmailUser = $email !== '' ? User::where('email', $email)->with('student')->first() : null;
            if ($existingEmailUser && optional($existingEmailUser->student)->nim !== $nim) {
                $email = $this->generateStudentEmail($name, $nim, true);
            }

            $validator = validator([
                'name' => $name,
                'nim' => $nim,
                'email' => $email,
                'class' => $class,
                'semester' => $semester,
                'phone_number' => $phone,
                'gender' => $gender,
                'address' => $address,
                'religion' => $religion,
                'prodi' => $prodi,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'nim' => ['required', 'string', Rule::unique('students', 'nim')->ignore(optional(Student::where('nim', $nim)->first())->id)],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(optional(User::where('email', $email)->first())->id)],
                'class' => ['required', 'string', 'max:50'],
                'semester' => ['required', 'integer', 'min:1', 'max:8'],
                'phone_number' => ['nullable', 'string', 'max:20'],
                'gender' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
                'address' => ['nullable', 'string', 'max:255'],
                'religion' => ['nullable', 'string', 'in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya'],
                'prodi' => ['required', 'string', 'in:Sistem Informasi Bisnis,Teknik Informatika'],
            ]);

            if ($validator->fails()) {
                $skipped[] = "Baris {$rowNumber}: " . $validator->errors()->first();
                continue;
            }

            $existingUser = User::where('email', $email)->first();
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $existingUser ? $existingUser->password : Hash::make($nim),
                    'profile_photo' => $existingUser?->profile_photo ?? 'profile_photos/default-3d.svg',
                ]
            );
            $user->assignRole('student');

            $student = Student::updateOrCreate(
                ['nim' => $nim],
                [
                    'user_id' => $user->id,
                    'address' => $address ?: null,
                    'religion' => $religion ?: null,
                    'gender' => $gender ?: null,
                    'phone_number' => $phone ?: null,
                    'prodi' => $prodi,
                    'semester' => (int) $semester,
                    'class' => $class,
                ]
            );

            $student->load('ranks');
            $student->updateRank();

            $student->wasRecentlyCreated ? $created++ : $updated++;
        }

        $message = "Import selesai. {$created} mahasiswa baru, {$updated} mahasiswa diperbarui.";
        if ($skipped) {
            $message .= ' Dilewati: ' . implode(' | ', array_slice($skipped, 0, 5));
        }

        return redirect()->route('admin.students.index')->with($skipped ? 'error' : 'success', $message);
    }

    protected function generateStudentEmail(string $name, string $nim, bool $includeNim = false): string
    {
        $localPart = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();

        if ($localPart === '') {
            $localPart = 'mahasiswa';
        }

        if ($includeNim) {
            $localPart .= preg_replace('/\D+/', '', $nim);
        }

        return $localPart . '@kunci.cloud';
    }

    protected function normalizeImportRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $cleanKey = strtolower(trim((string) $key));
            $cleanKey = str_replace([' ', '-', '.', '/'], '_', $cleanKey);
            $normalized[$cleanKey] = $value;
        }

        return $normalized;
    }

    protected function readXlsxRecords(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            $shared?->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            foreach ($shared?->xpath('//s:si') ?: [] as $item) {
                $text = '';
                $item->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($item->xpath('.//s:t') ?: [] as $part) {
                    $text .= (string) $part;
                }
                $sharedStrings[] = $text;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $sheet?->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($sheet?->xpath('//s:sheetData/s:row') ?: [] as $row) {
            $row->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $values = [];

            foreach ($row->xpath('s:c') ?: [] as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) ($attributes['r'] ?? '');
                $column = preg_replace('/[^A-Z]/', '', strtoupper($reference));
                $index = $this->columnLettersToIndex($column);
                $type = (string) ($attributes['t'] ?? '');

                $value = '';
                if ($type === 's') {
                    $raw = (string) ($cell->v ?? '');
                    $value = $sharedStrings[(int) $raw] ?? '';
                } elseif ($type === 'inlineStr') {
                    $cell->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $value = (string) (($cell->xpath('.//s:t') ?: [''])[0] ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                if ($index >= 0) {
                    $values[$index] = trim($value);
                }
            }

            if ($values) {
                ksort($values);
                $rows[] = $values;
            }
        }

        if (! $rows) {
            return [];
        }

        $headers = array_map(fn ($value) => strtolower(str_replace([' ', '-', '.', '/'], '_', trim((string) $value))), $rows[0]);
        $records = [];

        foreach (array_slice($rows, 1) as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $record[$header] = $row[$index] ?? '';
                }
            }
            $records[] = $record;
        }

        return $records;
    }

    protected function columnLettersToIndex(string $letters): int
    {
        if ($letters === '') {
            return -1;
        }

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    public function edit(Student $student)
    {
        $student->load(['user', 'ranks']);
        $challenges = Challenge::orderBy('title')->get();

        return view('admin.students.edit', compact('student', 'challenges'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'nim' => 'required|string|unique:students,nim,' . $student->id,
            'birth_date' => 'nullable|date',
            'religion' => 'required|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'prodi' => 'required|string|in:Sistem Informasi Bisnis,Teknik Informatika',
            'semester' => 'required|integer|min:1|max:8',
            'class' => 'nullable|string|max:50',
            'streak' => 'required|integer|min:0',
            'exp' => 'required|integer|min:0',
            'weekly_score' => 'required|integer|min:0',
            'total_score' => 'required|integer|min:0',
            'current_challenge_id' => 'nullable|exists:challenges,id',
            'profile_photo' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
        ]);

        $user = $student->user;
        $user->update([
            'name' => trim($request->name),
            'email' => $request->email,
        ]);

        if ($request->delete_photo === '1') {
            if ($user->profile_photo && $user->profile_photo !== 'profile_photos/default-3d.svg') {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->update(['profile_photo' => 'profile_photos/default-3d.svg']);
        } elseif ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo) && $user->profile_photo !== 'profile_photos/default-3d.svg') {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $photoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->update(['profile_photo' => $photoPath]);
        }

        $student->update([
            'nim' => $request->nim,
            'birth_date' => $request->birth_date,
            'religion' => $request->religion,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'class' => $request->class,
            'streak' => $request->streak,
            'exp' => $request->exp,
            'weekly_score' => $request->weekly_score,
            'total_score' => $request->total_score,
            'current_challenge_id' => $request->current_challenge_id,
        ]);

        $student->load('ranks');
        $student->updateRank();

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        if ($student->user->profile_photo && Storage::disk('public')->exists($student->user->profile_photo)) {
            Storage::disk('public')->delete($student->user->profile_photo);
        }

        $student->user->delete();
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa berhasil dihapus.');
    }
}
