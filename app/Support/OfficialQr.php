<?php

namespace App\Support;

use App\Models\Official;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use ZipArchive;

/** QR codes that point at an official's public page (same URL shape the old NukeViet cards used). */
class OfficialQr
{
    /** Public URL of the official's page. Uses QR_BASE_URL when set, otherwise APP_URL. */
    public static function url(Official $official): string
    {
        $base = rtrim((string) config('officials.qr_base_url') ?: config('app.url'), '/');

        return $base.route('officials.show', [$official->part?->slug ?? '-', $official->slug.'-'.$official->seq], false);
    }

    public static function png(Official $official, int $scale = 10): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render(self::url($official));
    }

    public static function filename(Official $official): string
    {
        $code = Str::slug($official->member_code);

        return ($code !== '' ? $code.'-' : '').$official->slug.'-'.$official->seq.'.png';
    }

    /** Builds a ZIP of QR PNGs in a temp file and returns its path (caller deletes it). */
    public static function zip(iterable $officials): string
    {
        set_time_limit(0); // thousands of codes can exceed the default 30s
        $path = tempnam(sys_get_temp_dir(), 'qr');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($officials as $official) {
            $zip->addFromString(self::filename($official), self::png($official));
        }
        $zip->close();

        return $path;
    }

    /** True when the base URL is a local/dev address, so printed codes would not work for real users. */
    public static function isLocalBase(): bool
    {
        $host = (string) parse_url((string) config('officials.qr_base_url') ?: config('app.url'), PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    }
}
