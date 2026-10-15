<?php

namespace App\Support;

use App\Models\Official;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/** Attaches portrait photos to officials in bulk, matching the file name (without extension) to the official's "Mã số". */
class OfficialImageImporter
{
    public const DIRECTORY = 'nhan-su-cac-tinh-thanh';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const MAX_IMAGE_BYTES = 8 * 1024 * 1024;

    private const MAX_ZIP_ENTRIES = 3000;

    /** @var array{attached:int, replaced:int, kept:int, not_found:string[], invalid:string[]} */
    private array $result = ['attached' => 0, 'replaced' => 0, 'kept' => 0, 'not_found' => [], 'invalid' => []];

    public function __construct(private bool $overwrite = false) {}

    /** @return array{attached:int, replaced:int, kept:int, not_found:string[], invalid:string[]} */
    public function import(string $path, string $originalName): array
    {
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) === 'zip') {
            $this->importZip($path, $originalName);
        } else {
            $this->importImage($path, $originalName);
        }

        return $this->result;
    }

    public function result(): array
    {
        return $this->result;
    }

    private function importZip(string $path, string $zipName): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->result['invalid'][] = "$zipName (không mở được file zip)";

            return;
        }

        if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
            $this->result['invalid'][] = "$zipName (quá ".self::MAX_ZIP_ENTRIES.' file, hãy chia nhỏ)';
            $zip->close();

            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = basename(str_replace('\\', '/', (string) $stat['name']));

            // Skip folders, macOS metadata and hidden files; only the base name is ever used, never the stored path.
            if ($name === '' || str_ends_with((string) $stat['name'], '/') || str_starts_with($name, '.') || str_contains((string) $stat['name'], '__MACOSX')) {
                continue;
            }
            if ($stat['size'] > self::MAX_IMAGE_BYTES) {
                $this->result['invalid'][] = "$name (lớn hơn 8MB)";

                continue;
            }

            $stream = $zip->getStream($stat['name']);
            if (! $stream) {
                $this->result['invalid'][] = "$name (không đọc được)";

                continue;
            }
            file_put_contents($tmp, stream_get_contents($stream, self::MAX_IMAGE_BYTES + 1));
            fclose($stream);

            $this->importImage($tmp, $name);
        }
        @unlink($tmp);
        $zip->close();
    }

    private function importImage(string $path, string $name): void
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $code = trim(pathinfo($name, PATHINFO_FILENAME));

        if (! in_array($ext, self::EXTENSIONS, true) || $code === '') {
            $this->result['invalid'][] = "$name (không phải ảnh jpg/png/webp/gif)";

            return;
        }
        if (filesize($path) > self::MAX_IMAGE_BYTES || @getimagesize($path) === false) {
            $this->result['invalid'][] = "$name (không phải ảnh hợp lệ hoặc lớn hơn 8MB)";

            return;
        }

        $official = Official::where('member_code', $code)->first();
        if (! $official) {
            $this->result['not_found'][] = $name;

            return;
        }

        $hadImage = $official->image !== '';
        if ($hadImage && ! $this->overwrite) {
            $this->result['kept']++;

            return;
        }

        // The previous file is left on disk on purpose; only the reference changes.
        $stored = Storage::disk('officials')->putFileAs(self::DIRECTORY, new File($path), Str::slug($code).'-'.time().'.'.$ext);
        $official->update(['image' => $stored]);
        $this->result[$hadImage ? 'replaced' : 'attached']++;
    }
}
