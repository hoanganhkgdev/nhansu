<?php

namespace App\Support;

use App\Models\Official;
use App\Models\Part;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/** Imports officials from an Excel/CSV file, matching columns by header name (column order does not matter). */
class OfficialImporter
{
    /** Header titles used by the template file. */
    public const COLUMNS = [
        'fullname' => 'Họ và tên',
        'dharma_name' => 'Pháp danh',
        'birthday' => 'Ngày sinh',
        'member_code' => 'Mã số',
        'resident' => 'Địa chỉ thường trú',
        'part' => 'Tỉnh/Thành',
        'position' => 'Chức vụ',
        'precept_rank' => 'Giới phẩm',
        'clergy_rank' => 'Giáo phẩm',
        'education' => 'Trình độ học vấn',
        'email' => 'Email',
        'phone' => 'Điện thoại',
        'id_number' => 'Số CMND/CCCD',
        'id_issued_date' => 'Ngày cấp',
        'id_issued_place' => 'Nơi cấp',
        'is_priority' => 'Ưu tiên',
    ];

    /** Other accepted header spellings (normalised) -> field. */
    private const ALIASES = [
        'hoten' => 'fullname', 'hovaten' => 'fullname', 'phapdanh' => 'dharma_name', 'ngaythangnamsinh' => 'birthday',
        'maso' => 'member_code', 'diachi' => 'resident', 'noithuongtru' => 'resident', 'tinh' => 'part',
        'thanhpho' => 'part', 'donvi' => 'part', 'tinhthanhpho' => 'part', 'trinhdo' => 'education',
        'dienthoai' => 'phone', 'sodienthoai' => 'phone', 'cmnd' => 'id_number', 'cccd' => 'id_number',
        'socmnd' => 'id_number', 'ngaycapcmnd' => 'id_issued_date', 'noicap' => 'id_issued_place', 'uutien' => 'is_priority',
    ];

    private const TEXT_FIELDS = [
        'dharma_name', 'member_code', 'resident', 'position', 'precept_rank', 'clergy_rank',
        'education', 'email', 'phone', 'id_number', 'id_issued_place',
    ];

    /** @var array<string, int> */
    private array $parts = [];

    public function __construct(private ?int $defaultPartId = null, private bool $update = false)
    {
        foreach (Part::all() as $p) {
            $this->parts['id:'.$p->id] = $p->id;
            $this->parts['t:'.self::norm($p->title)] = $p->id;
            $this->parts['t:'.self::norm($p->slug)] = $p->id;
        }
    }

    /** @return array{created:int, updated:int, skipped:int, errors:string[]} */
    public function import(string $path, string $ext): array
    {
        $reader = strtolower($ext) === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $map = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            $line = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($c) => $c->getValue(), $row->getCells());

                if ($map === null) {
                    $candidate = $this->headerMap($cells);
                    $map = in_array('fullname', $candidate, true) ? $candidate : null; // not the header row yet

                    continue;
                }

                if (implode('', array_map(fn ($v) => trim((string) $v), $cells)) === '') {
                    continue;
                }

                try {
                    $this->importRow($map, $cells, $line, $result);
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = "Dòng $line: ".$e->getMessage();
                }
            }
            break; // first sheet only
        }
        $reader->close();

        if ($map === null) {
            $result['errors'][] = 'Không tìm thấy dòng tiêu đề (cần có cột "Họ và tên"). Hãy tải file mẫu.';
        }

        return $result;
    }

    public static function writeTemplate(string $path): void
    {
        $w = new XlsxWriter;
        $w->openToFile($path);
        $w->addRow(Row::fromValues(array_values(self::COLUMNS)));
        $w->close();
    }

    private function headerMap(array $cells): array
    {
        $byNorm = self::ALIASES;
        foreach (self::COLUMNS as $field => $title) {
            $byNorm[self::norm($title)] = $field;
            $byNorm[self::norm($field)] = $field;
        }

        $map = [];
        foreach ($cells as $i => $v) {
            $n = self::norm((string) $v);
            if (isset($byNorm[$n])) {
                $map[$i] = $byNorm[$n];
            }
        }

        return $map;
    }

    private function importRow(array $map, array $cells, int $line, array &$result): void
    {
        $d = [];
        foreach ($map as $i => $field) {
            $d[$field] = $cells[$i] ?? null;
        }

        $fullname = preg_replace('/\s+/u', ' ', $this->text($d['fullname'] ?? null));
        if ($fullname === '') {
            throw new \RuntimeException('thiếu Họ và tên');
        }

        $partName = $this->text($d['part'] ?? null);
        $partId = $this->resolvePart($partName);
        if ($partName !== '' && ! $partId) {
            throw new \RuntimeException('không tìm thấy Tỉnh/Thành "'.$partName.'"');
        }

        $data = ['fullname' => $fullname, 'part_id' => $partId, 'is_priority' => $this->truthy($d['is_priority'] ?? null)];
        foreach (self::TEXT_FIELDS as $k) {
            $data[$k] = $this->text($d[$k] ?? null);
        }

        foreach (['birthday' => 'Ngày sinh', 'id_issued_date' => 'Ngày cấp'] as $k => $label) {
            $raw = $d[$k] ?? null;
            $data[$k] = $this->parseDate($raw);
            if ($data[$k] === null && $this->text($raw) !== '') {
                $result['errors'][] = "Dòng $line: $label \"{$this->text($raw)}\" không đọc được (cần dd/mm/yyyy), để trống.";
            }
        }

        $existing = ($this->update && $data['member_code'] !== '')
            ? Official::where('member_code', $data['member_code'])->first() : null;

        if ($existing) {
            // Never overwrite existing values with blanks.
            $existing->update(array_filter($data, fn ($v) => $v !== '' && $v !== null));
            $result['updated']++;
        } else {
            Official::create($data + ['is_active' => true]);
            $result['created']++;
        }
    }

    private function resolvePart(string $value): ?int
    {
        if ($value === '') {
            return $this->defaultPartId;
        }
        if (ctype_digit($value) && isset($this->parts['id:'.$value])) {
            return (int) $value;
        }

        return $this->parts['t:'.self::norm($value)] ?? null;
    }

    private function text(mixed $v): string
    {
        if ($v instanceof DateTimeInterface) {
            return $v->format('d/m/Y');
        }
        if (is_float($v) && floor($v) == $v) {
            $v = (int) $v;
        }

        return trim((string) $v);
    }

    private function parseDate(mixed $v): ?string
    {
        if ($v instanceof DateTimeInterface) {
            return $v->format('Y-m-d');
        }

        $s = $this->text($v);
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $s, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})#', $s, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    private function truthy(mixed $v): bool
    {
        return in_array(Str::lower($this->text($v)), ['1', 'x', 'co', 'có', 'yes', 'true'], true);
    }

    private static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));
    }
}
