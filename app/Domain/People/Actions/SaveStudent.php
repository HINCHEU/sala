<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Student;
use App\Domain\People\StudentCodes;
use App\Domain\School\Models\School;
use App\Support\Files\FileStore;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;

class SaveStudent
{
    public const PHOTO_MAX_PX = 600;

    public function __construct(private FileStore $files, private CurrentSchool $current) {}

    /**
     * Creates the student when $student is null. A blank student code gets the next free number.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(?Student $student, array $data, ?UploadedFile $photo = null): Student
    {
        return DB::transaction(function () use ($student, $data, $photo) {
            $creating = $student === null;
            $student ??= new Student;

            if (blank($data['student_code'] ?? null)) {
                // Serialises code generation per school; see StudentCodes.
                School::whereKey($student->school_id ?? $this->current->idOrFail())->lockForUpdate()->first();
                $data['student_code'] = $student->student_code ?: (new StudentCodes)->next();
            }

            $student->fill($data)->save();

            if ($photo) {
                $this->replacePhoto($student, $photo);
            }

            activity()->performedOn($student)->causedBy(auth()->user())->log($creating ? 'student.created' : 'student.updated');

            return $student;
        });
    }

    private function replacePhoto(Student $student, UploadedFile $photo): void
    {
        // Re-encoding drops EXIF (a phone photo's location) and anything hidden in the upload.
        $jpeg = (string) ImageManager::gd()->read($photo->getRealPath())
            ->scaleDown(self::PHOTO_MAX_PX, self::PHOTO_MAX_PX)->toJpeg(85);

        if ($old = $student->photo) {
            $this->files->delete($old);
        }

        $this->files->putContents($jpeg, 'jpg', 'image/jpeg', $student, 'photo', $student->school_id, originalName: $photo->getClientOriginalName());
        $student->unsetRelation('photo');
    }
}
