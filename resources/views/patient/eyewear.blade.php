@extends('patient.layout')

@section('title', 'Eyewear Frame Showcase')

@section('content')
<div class="patient-page-head">
    <div>
        <div class="eyebrow" style="color: var(--moa-bronze);">CURATED EYEWEAR COLLECTION</div>
        <h1>Find frames that fit <em>your signature style.</em></h1>
        <p>Explore handcrafted titanium and artisan acetate frames. Reserve up to 2 frames online for a personalized in-store fitting.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if(session('info'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-gold); background: rgba(240, 186, 127, 0.15); color: #7A5530;">
        <i class="bx bx-info-circle"></i> {{ session('info') }}
    </p>
@endif

<!-- Filters Bar -->
<form method="GET" action="{{ route('patient.eyewear') }}" style="background: #FAF7F2; border: 1px solid #EBE4DC; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
    <label style="flex: 1.5; min-width: 180px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
        Search Brand / Model
        <input type="text" name="q" value="{{ request('q') }}" placeholder="e.g. Titanium, Komorebi, Amber..." style="padding: 8px 12px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
    </label>

    <label style="flex: 1; min-width: 140px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
        Face Shape
        <select name="shape" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            <option value="">All Shapes</option>
            <option value="round" @selected(request('shape') === 'round')>Round</option>
            <option value="rectangular" @selected(request('shape') === 'rectangular')>Rectangular</option>
            <option value="cat-eye" @selected(request('shape') === 'cat-eye')>Cat-Eye</option>
            <option value="aviator" @selected(request('shape') === 'aviator')>Aviator</option>
            <option value="geometric" @selected(request('shape') === 'geometric')>Geometric</option>
        </select>
    </label>

    <label style="flex: 1; min-width: 140px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
        Category
        <select name="category" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            <option value="">All Styles</option>
            <option value="spectacle" @selected(request('category') === 'spectacle')>Prescription Glasses</option>
            <option value="sunglasses" @selected(request('category') === 'sunglasses')>Sunglasses</option>
            <option value="reading" @selected(request('category') === 'reading')>Reading Glasses</option>
        </select>
    </label>

    <label style="flex: 1; min-width: 140px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
        Material
        <select name="material" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            <option value="">All Materials</option>
            <option value="titanium" @selected(request('material') === 'titanium')>Pure Titanium</option>
            <option value="acetate" @selected(request('material') === 'acetate')>Handcrafted Acetate</option>
            <option value="ultem" @selected(request('material') === 'ultem')>Lightweight Ultem</option>
        </select>
    </label>

    <button type="submit" class="button button-teal" style="padding: 9px 18px; font-size: 13px;">
        Filter
    </button>
    @if(request()->anyFilled(['q', 'shape', 'category', 'material', 'gender']))
        <a href="{{ route('patient.eyewear') }}" class="button button-quiet" style="font-size: 13px; padding: 9px 14px;">Reset</a>
    @endif
</form>

<!-- Frames Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 24px; margin-bottom: 32px;">
    @forelse($frames as $frame)
    <article style="background: #fff; border: 1px solid #E5DFD7; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: transform 0.2s ease, box-shadow 0.2s ease;">
        <div>
            <div style="height: 160px; display: flex; align-items: center; justify-content: center; background: #FAF7F2; border-radius: 8px; margin-bottom: 16px; overflow: hidden; position: relative;">
                <img src="{{ $frame->image_url ?: '/asset/img/prescription.png' }}" alt="{{ $frame->model_name }}" style="max-height: 120px; object-fit: contain;">
                <span style="position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; background: rgba(159, 101, 44, 0.15); color: var(--moa-bronze);">
                    {{ ucfirst($frame->shape) }}
                </span>
            </div>

            <div style="font-size: 11px; text-transform: uppercase; color: var(--moa-slate); font-weight: 700; letter-spacing: 0.5px;">
                {{ $frame->brand }}
            </div>
            <h2 style="margin: 4px 0 6px; font-size: 18px; font-family: 'Alegreya SC', serif;">
                {{ $frame->model_name }}
            </h2>

            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                <span class="staff-role-badge" style="font-size: 10px;">{{ ucfirst($frame->material) }}</span>
                <span class="staff-role-badge" style="font-size: 10px; background: #F2EBE3;">{{ $frame->color }}</span>
                <span class="staff-role-badge" style="font-size: 10px; background: #E8F4F2; color: #164e63;">
                    Size: {{ $frame->eye_size }}-{{ $frame->bridge_size }}-{{ $frame->temple_length }}
                </span>
            </div>
        </div>

        <div style="border-top: 1px solid #EFEAE3; padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 11px; color: var(--moa-slate);">Retail Price</div>
                <div style="font: 700 20px/1 'Alegreya SC', serif; color: var(--moa-bronze);">
                    ₱{{ number_format($frame->retail_price, 2) }}
                </div>
            </div>

            @if(in_array($frame->id, $userReservations))
                <span style="font-size: 11px; color: #164e63; font-weight: 700; background: rgba(100, 188, 180, 0.2); padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bx bx-check-circle"></i> Held for You
                </span>
            @else
                <form method="POST" action="{{ route('patient.eyewear.reserve', $frame) }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="button button-quiet" style="font-size: 11px; padding: 6px 12px; border-color: var(--moa-bronze); color: var(--moa-bronze); display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bx bx-bookmark-plus"></i> Reserve Try-On
                    </button>
                </form>
            @endif
        </div>
    </article>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--moa-slate); background: #FAF7F2; border-radius: 12px;">
        <h3>No frames found matching your selected criteria.</h3>
        <p>Try resetting your filters or search keywords.</p>
    </div>
    @endforelse
</div>

{{ $frames->links() }}
@endsection
