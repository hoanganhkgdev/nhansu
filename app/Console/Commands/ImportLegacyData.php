<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Signature('legacy:import {--fresh : Empty the target tables first}')]
#[Description('Import parts, officials and users from the old NukeViet database (connection "legacy")')]
class ImportLegacyData extends Command
{
    /** Old NukeViet module table prefix and the folder its photos were stored in. */
    private const LEGACY_PREFIX = 'nv4_vi_nhan_su_cac_tinh_thanh';

    private const IMAGE_DIR = 'nhan-su-cac-tinh-thanh';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        if ($this->option('fresh')) {
            $this->truncateTargets();
        } elseif (DB::table('officials')->exists() || DB::table('users')->exists()) {
            $this->error('Target tables are not empty. Re-run with --fresh to replace them.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($legacy) {
            $this->importModule($legacy, self::LEGACY_PREFIX);
            $this->importUsers($legacy);
        });

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function truncateTargets(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['officials', 'parts', 'users'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();
    }

    private function importModule($legacy, string $prefix): void
    {
        $now = now();

        // Parts (old ids -> new ids).
        $partMap = [];
        $oldParts = $legacy->table("{$prefix}_part")->orderBy('id')->get();
        foreach ($oldParts as $p) {
            $partMap[$p->id] = DB::table('parts')->insertGetId([
                'title' => trim($p->title),
                'slug' => $this->uniquePartSlug($p->alias ?: Str::slug($p->title) ?: 'bo-phan'),
                'office' => $p->office,
                'address' => $p->address,
                'phone' => $p->phone,
                'fax' => $p->fax,
                'website' => $p->website,
                'email' => $p->email,
                'note' => $p->note,
                'is_active' => (bool) $p->status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $officialMap = [];
        foreach ($legacy->table("{$prefix}_rows")->orderBy('id')->cursor() as $r) {
            $r = (array) $r;

            $officialMap[$r['id']] = DB::table('officials')->insertGetId([
                'part_id' => $partMap[$r['part_id']] ?? null,
                'seq' => $r['id'],
                'fullname' => trim(trim($r['lastname']).' '.trim($r['firstname'])),
                'dharma_name' => trim($r['phapdanh']),
                'birthday' => $this->birthday($r),
                'image' => $r['image'] !== '' ? self::IMAGE_DIR."/{$r['image']}" : '',
                'email' => $r['email'],
                'phone' => $r['phone'],
                'member_code' => trim($r['party_date_code']),
                'id_number' => trim($r['socmnd']),
                'id_issued_date' => $r['ngaycapcmnd'] > 0 ? Carbon::createFromTimestamp($r['ngaycapcmnd'])->toDateString() : null,
                'id_issued_place' => $r['noicapcmnd'],
                'position' => trim($r['chucvu'] ?? ''),
                'resident' => trim($r['resident']),
                'precept_rank' => trim($r['nation']),
                'clergy_rank' => trim($r['religion']),
                'education' => trim($r['education']),
                'sort' => $r['weight'],
                'is_priority' => $r['prior'] > 0,
                'is_active' => (bool) $r['status'],
                'created_at' => $r['addtime'] ? Carbon::createFromTimestamp($r['addtime']) : $now,
                'updated_at' => $r['edittime'] ? Carbon::createFromTimestamp($r['edittime']) : $now,
            ]);
        }

        $this->line(sprintf('%d parts, %d officials', count($partMap), count($officialMap)));
    }

    /** Date of birth as Y-m-d, or null when the old record has no usable date. */
    private function birthday(array $r): ?string
    {
        $day = (int) $r['birthday_day'];
        $month = (int) $r['birthday_month'];
        $year = (int) $r['birthday_year'];

        if (! $year && $r['birthday'] != 0) {
            return Carbon::createFromTimestamp($r['birthday'])->toDateString();
        }

        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    private function uniquePartSlug(string $slug): string
    {
        $candidate = $slug;
        $n = 2;
        while (DB::table('parts')->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$n++;
        }

        return $candidate;
    }

    private function importUsers($legacy): void
    {
        $levels = $legacy->table('nv4_authors')->pluck('lev', 'admin_id');
        $roles = [1 => 'super_admin', 2 => 'admin', 3 => 'editor'];
        $count = 0;

        foreach ($legacy->table('nv4_users')->orderBy('userid')->get() as $u) {
            $name = trim($u->first_name.' '.$u->last_name) ?: $u->username;
            DB::table('users')->insert([
                'name' => $name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $roles[$levels[$u->userid] ?? 0] ?? 'none',
                'is_active' => (bool) $u->active,
                // Legacy {SSHA512} hash is kept; LegacyAwareHasher verifies it and upgrades to bcrypt on first login.
                'password' => $u->password,
                'created_at' => Carbon::createFromTimestamp($u->regdate),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->line("users                      {$count} users");
    }
}
