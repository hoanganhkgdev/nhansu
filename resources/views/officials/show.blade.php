@extends('layouts.app')

@section('title', $official->fullname.' - Nhân Sự Các Tỉnh')

@push('head')
<style>
    .profile { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 28px -14px rgb(7 58 33 / .35); }
    .profile-hero { background: linear-gradient(160deg, #0a4a2a 0%, #007a39 100%); padding: 28px 20px 64px; text-align: center; color: #fff; }
    .profile-hero .org { margin: 0; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; opacity: .8; }
    .profile-body { padding: 0 20px 24px; text-align: center; }
    .avatar { width: 168px; height: 168px; margin: -52px auto 14px; border-radius: 50%; border: 5px solid #fff; background: var(--brand-soft); box-shadow: 0 6px 18px -6px rgb(0 0 0 / .35); overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }
    .avatar span { font-size: 4rem; font-weight: 700; color: var(--brand); }
    .profile h1 { margin: 0; font-size: 1.6rem; line-height: 1.25; }
    .dharma { margin: 4px 0 0; font-size: 1.1rem; font-weight: 600; color: var(--brand); }
    .chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 14px 0 0; }
    .chip { display: inline-block; padding: 4px 12px; border-radius: 999px; background: var(--brand-soft); color: var(--brand-dark); font-size: .875rem; font-weight: 600; text-decoration: none; }
    .position { margin: 14px 0 0; font-size: 1rem; color: #33443a; }
    .facts { display: block; grid-template-columns: none; gap: 0; margin: 22px 0 0; border-top: 1px solid var(--line); text-align: left; }
    .facts div { display: grid; grid-template-columns: 130px 1fr; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
    .facts dt { color: var(--muted); font-size: .9rem; font-weight: 500; }
    .facts dd { margin: 0; font-weight: 500; word-break: break-word; }
    .facts .mono { font-variant-numeric: tabular-nums; letter-spacing: .02em; }
    .profile .actions { justify-content: center; margin-top: 22px; flex-wrap: wrap; }
    .btn.ghost { background: #fff; color: var(--brand); border: 1px solid var(--line); }
    @media (max-width: 560px) {
        .profile { border-radius: 0; border-left: 0; border-right: 0; margin: -24px -16px 0; max-width: none; }
        .facts div { grid-template-columns: 1fr; gap: 2px; }
        .facts dd { margin-bottom: 0; }
    }
    @media print {
        .profile { box-shadow: none; border: 0; }
        .profile-hero { background: #fff !important; color: #000; padding: 0; }
        .profile-hero .org { display: none; }
        .avatar { margin-top: 0; box-shadow: none; }
    }
</style>
@endpush

@section('content')
    <article class="profile">
        <div class="profile-hero">
            <p class="org">Giáo hội Phật giáo Việt Nam</p>
        </div>

        <div class="profile-body">
            <div class="avatar">
                @if ($official->image_url)
                    <img src="{{ $official->image_url }}" alt="{{ $official->fullname }}">
                @else
                    <span aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(collect(explode(' ', $official->dharma_name ?: $official->fullname))->last() ?? '?'), 0, 1)) }}</span>
                @endif
            </div>

            <h1>{{ $official->fullname }}</h1>
            @if ($official->dharma_name)<p class="dharma">{{ $official->dharma_name }}</p>@endif

            @if ($official->part || $official->clergy_rank)
                <div class="chips">
                    @if ($official->part)<span class="chip">{{ $official->part->title }}</span>@endif
                    @if ($official->clergy_rank)<span class="chip">{{ $official->clergy_rank }}</span>@endif
                </div>
            @endif

            @if ($official->position)<p class="position">{{ $official->position }}</p>@endif

            {{-- Name, dharma name, province, rank and position already appear above; only the rest is listed here.
                 ID numbers, phone and email stay admin-only. --}}
            @if ($official->birthday || $official->resident || $official->member_code)
                <dl class="facts">
                    @if ($official->birthday)<div><dt>Ngày sinh</dt><dd>{{ $official->birthday->format('d/m/Y') }}</dd></div>@endif
                    @if ($official->resident)<div><dt>Thường trú</dt><dd>{{ $official->resident }}</dd></div>@endif
                    @if ($official->member_code)<div><dt>Mã số</dt><dd class="mono">{{ $official->member_code }}</dd></div>@endif
                </dl>
            @endif

            <div class="actions">
                <a class="btn ghost" href="#" onclick="window.print(); return false;">In thông tin</a>
            </div>
        </div>
    </article>
    @if ($print)
        <script>window.print();</script>
    @endif
@endsection
