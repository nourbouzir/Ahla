<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Profile pictures. Every upload is decoded and re-encoded as a 256x256 square
 * (centre crop) WebP/JPEG with GD, which also removes EXIF data such as GPS
 * position, and guarantees a small file whatever the user sent. Stored on the
 * public disk under avatars/, one file per user.
 */
class AvatarService
{
    public const SIZE = 256;

    /** Refuse absurd dimensions before decoding: a 30000x30000 PNG would exhaust memory. */
    private const MAX_SIDE = 6000;

    public function store(User $user, UploadedFile $file): string
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || $info[0] < 1 || $info[1] < 1 || max($info[0], $info[1]) > self::MAX_SIDE) {
            throw new InvalidArgumentException('Unreadable or too large image.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new InvalidArgumentException('Unreadable image.');
        }

        [$width, $height] = [imagesx($source), imagesy($source)];
        $side = min($width, $height);
        $square = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($square, true);
        imagefill($square, 0, 0, imagecolorallocate($square, 250, 248, 243));
        imagecopyresampled($square, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), self::SIZE, self::SIZE, $side, $side);

        $webp = function_exists('imagewebp');
        ob_start();
        $webp ? imagewebp($square, null, 85) : imagejpeg($square, null, 85);
        $binary = (string) ob_get_clean();

        $path = 'avatars/'.$user->id.'-'.Str::random(12).($webp ? '.webp' : '.jpg');
        Storage::disk('public')->put($path, $binary);

        $this->delete($user);
        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public function delete(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
