<?php

namespace App\Domain\School\Actions;

use App\Domain\School\BrandColor;
use App\Domain\School\Models\School;
use App\Domain\School\Settings\SchoolSettings;
use App\Support\Files\FileStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;

class UpdateSchoolSettings
{
    public const LOGO_MAX_PX = 512;

    public function __construct(private FileStore $files) {}

    /**
     * @param  array<string, mixed>  $profile  name_km, name_en, report_header, brand_color
     */
    public function handle(School $school, array $profile, SchoolSettings $settings, ?UploadedFile $logo = null): void
    {
        DB::transaction(function () use ($school, $profile, $settings, $logo) {
            $color = $profile['brand_color'] ?? null;

            $school->fill([
                ...$profile,
                'brand_color' => $color,
                'brand_color_soft' => $color ? BrandColor::soft($color) : null,
                'settings' => $settings,
            ])->save();

            if ($logo) {
                $this->replaceLogo($school, $logo);
            }
        });
    }

    private function replaceLogo(School $school, UploadedFile $logo): void
    {
        // Re-encoding drops EXIF (location, camera) and anything hidden in the upload.
        $png = (string) ImageManager::gd()->read($logo->getRealPath())->scaleDown(self::LOGO_MAX_PX, self::LOGO_MAX_PX)->toPng();

        if ($old = $school->logo) {
            $this->files->delete($old);
        }

        $this->files->putContents($png, 'png', 'image/png', $school, 'logo', $school->id, public: true, originalName: $logo->getClientOriginalName());
        $school->unsetRelation('logo');
    }
}
