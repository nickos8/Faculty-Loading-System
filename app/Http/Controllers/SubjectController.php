<?php

namespace App\Http\Controllers;

// app/Http/Controllers/SubjectController.php

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->get('per_page', 25);
        $perPage = max(10, min($perPage, 200)); // clamp 10..200

        $query = Subject::query()
            ->select(['id', 'code', 'name', 'program_id'])
            ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id));

        if ($request->filled('search')) {
            $term = trim((string) $request->search);

            $query->where(function ($q) use ($term) {
                // Code prefix is faster than %term% on many DBs
                $q->where('code', 'like', $term . '%')
                  ->orWhere('name', 'like', '%' . $term . '%');
            });
        }

        // Only load prerequisite code/id (used in index page pills)
        $subjects = $query
            ->with(['prerequisites:id,code'])
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        $user = auth()->user();

        // Only load prerequisites that are already selected (usually none).
        $selectedPrereqIds = collect(old('prerequisite_ids', []))
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $selectedPrereqItems = empty($selectedPrereqIds)
            ? collect()
            : Subject::query()
                ->select(['id', 'code', 'name'])
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->whereIn('id', $selectedPrereqIds)
                ->orderBy('code')
                ->get();

        return view('subjects.create', compact('selectedPrereqIds', 'selectedPrereqItems'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to create a subject.');
        }

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'code'),
            ],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        // ✅ Custom "duplicate (code + name)" check (keeps your existing behavior)
        $validator->after(function ($v) use ($request, $user) {
            $name = trim((string) $request->input('name'));
            $code = trim((string) $request->input('code'));

            if ($name === '' || $code === '') return;

            $exists = Subject::query()
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->where('code', $code)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                $v->errors()->add('name', 'This subject Name already exists');
            }

            // ✅ Extra safety: make sure selected prerequisites belong to the same program (if program admin)
            $ids = collect($request->input('prerequisite_ids', []))
                ->filter()
                ->map(fn ($x) => (int) $x)
                ->unique()
                ->values();

            if ($ids->isEmpty()) return;

            $count = Subject::query()
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->whereIn('id', $ids)
                ->count();

            if ($count !== $ids->count()) {
                $v->errors()->add('prerequisite_ids', 'Some prerequisites are invalid for your program.');
            }
        });

        $validated = $validator->validate();

        $subject = Subject::create([
            'name'       => $validated['name'],
            'code'       => $validated['code'],
            'created_by' => $user->id,
            'program_id' => $user->program_id,
        ]);

        $subject->prerequisites()->sync($validated['prerequisite_ids'] ?? []);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        $user = auth()->user();

        $subject->load('prerequisites:id,code,name');

        $selectedPrereqIds = collect(old('prerequisite_ids', $subject->prerequisites->pluck('id')->all()))
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $selectedPrereqItems = empty($selectedPrereqIds)
            ? collect()
            : Subject::query()
                ->select(['id', 'code', 'name'])
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->whereIn('id', $selectedPrereqIds)
                ->orderBy('code')
                ->get();

        return view('subjects.edit', compact('subject', 'selectedPrereqIds', 'selectedPrereqItems'));
    }

    public function update(Request $request, Subject $subject)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'code')->ignore($subject->id),
            ],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:subjects,id', 'not_in:' . $subject->id],
        ]);

        $validator->after(function ($v) use ($request, $user, $subject) {
            $name = trim((string) $request->input('name'));
            $code = trim((string) $request->input('code'));

            if ($name === '' || $code === '') return;

            $exists = Subject::query()
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->where('code', $code)
                ->where('name', $name)
                ->where('id', '!=', $subject->id)
                ->exists();

            if ($exists) {
                $v->errors()->add('code', 'Another subject already exists with the same code and name.');
                $v->errors()->add('name', 'Another subject already exists with the same code and name.');
            }

            // ✅ Extra safety: prerequisites must match program (if program admin)
            $ids = collect($request->input('prerequisite_ids', []))
                ->filter()
                ->map(fn ($x) => (int) $x)
                ->unique()
                ->values();

            if ($ids->isEmpty()) return;

            $count = Subject::query()
                ->when($user->program_id, fn ($q) => $q->where('program_id', $user->program_id))
                ->whereIn('id', $ids)
                ->count();

            if ($count !== $ids->count()) {
                $v->errors()->add('prerequisite_ids', 'Some prerequisites are invalid for your program.');
            }
        });

        $validated = $validator->validate();

        $subject->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
        ]);

        $subject->prerequisites()->sync($validated['prerequisite_ids'] ?? []);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * AJAX lookup for prerequisites (fast for thousands of subjects).
     * GET /subjects/lookup?q=CS&exclude=123
     */
    public function lookup(Request $request)
    {
        $user = $request->user();

        $q = trim((string) $request->get('q', ''));
        $exclude = (int) $request->get('exclude', 0);

        $query = Subject::query()
            ->select(['id', 'code', 'name'])
            ->where('status', 'active')
            ->when($user->program_id, fn ($qq) => $qq->where('program_id', $user->program_id))
            ->when($exclude > 0, fn ($qq) => $qq->where('id', '!=', $exclude));

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', $q . '%')
                  ->orWhere('name', 'like', '%' . $q . '%');
            });
        }

        return response()->json(
            $query->orderBy('code')->limit(30)->get()
        );
    }
}
