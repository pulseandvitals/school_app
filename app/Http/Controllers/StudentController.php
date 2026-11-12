<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\ActivityLog;
use App\Models\HabitatMember;
use App\Models\Student;
use App\Services\Students\StudentProfileUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher's full student roster across every aquarium and garden.
 */
class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        $students = $request->user()->students()
            ->with(['memberships' => fn ($query) => $query->whereHas('habitat'), 'memberships.habitat', 'memberships.gameAsset'])
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'student_number' => $student->student_number,
                'photo_url' => $student->photo_url,
                'memberships' => $student->memberships->map(fn (HabitatMember $member): array => [
                    'id' => $member->id,
                    'habitat_id' => $member->habitat_id,
                    'habitat_name' => $member->habitat->name,
                    'type' => $member->habitat->type->value,
                    'route' => $member->habitat->type->routePrefix(),
                    'image_url' => $member->gameAsset->image_url,
                    'stage' => $member->growth_stage,
                    'points' => $member->growth_points,
                ]),
            ]);

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }

    public function edit(Student $student): Response
    {
        Gate::authorize('update', $student);

        return Inertia::render('Students/Edit', [
            'student' => $student->only(['id', 'name', 'student_number', 'photo_url']),
        ]);
    }

    public function update(StudentRequest $request, Student $student, StudentProfileUpdater $profiles): RedirectResponse
    {
        $profiles->save(
            $student,
            $request->safe()->only(['name', 'student_number']),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return to_route('students.index')->with('success', 'Student updated.');
    }

    /**
     * Removes the student from the roster and from every habitat. Records are
     * soft-deleted so the audit trail stays intact.
     */
    public function destroy(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        DB::transaction(function () use ($student): void {
            $student->memberships()->get()->each->delete();
            $student->delete();
        });

        ActivityLog::record($request->user(), 'student.deleted', $student, ['name' => $student->name]);

        return to_route('students.index')->with('success', "{$student->name} was removed.");
    }
}
