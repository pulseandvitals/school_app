<?php

namespace App\Services\Students;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StudentProfileUpdater
{
    public const PHOTO_DIRECTORY = 'student-photos';

    /**
     * @param  array{name: string, student_number?: string|null}  $attributes
     */
    public function save(Student $student, array $attributes, ?UploadedFile $photo = null, bool $removePhoto = false): Student
    {
        $student->fill([
            'name' => $attributes['name'],
            'student_number' => $attributes['student_number'] ?? null,
        ]);

        if (($photo || $removePhoto) && $student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
            $student->photo_path = null;
        }

        if ($photo) {
            $student->photo_path = $photo->store(self::PHOTO_DIRECTORY, 'public');
        }

        $student->save();

        return $student;
    }
}
