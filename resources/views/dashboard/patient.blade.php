<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Patient dashboard · MOA</title><link rel="stylesheet" href="/app.css"></head>
<body><main class="dashboard-page">
<header class="dashboard-header"><a class="brand" href="/"><span class="brand-mark"><i></i><i></i></span>MOA</a><form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="text-button">Log out</button></form></header>
<section class="dashboard-intro"><div class="eyebrow">PATIENT PORTAL</div><h1>Good to see you, <em>{{ $user->name }}</em></h1><p>Manage your care, appointments, and prescription history in one secure place.</p>@if(session('success'))<p class="success-message">{{ session('success') }}</p>@endif</section>
<div class="dashboard-grid">
<section class="dashboard-card"><h2>Request an appointment</h2><form method="POST" action="{{ route('appointments.store') }}" data-appointment-form>@csrf
<label>Service</label><select name="service" required><option value="">Choose a service</option><option>Comprehensive eye exam</option><option>Frame styling</option><option>Contact lens fitting</option></select>
<label>Date</label><input type="date" name="appointment_date" min="{{ now()->toDateString() }}" required data-slot-date>
<label>Available time</label><select name="starts_at" required data-slot-select disabled><option>Select a date first</option></select>
<label>Notes</label><input name="notes" maxlength="2000" placeholder="Anything your doctor should know?"><button class="button button-teal" type="submit">Request appointment <span>→</span></button>
</form><p class="auth-error" data-slot-error hidden></p></section>
<section class="dashboard-card"><h2>Profile</h2><p class="status-badge">{{ $user->hasVerifiedEmail() ? 'Email verified' : 'Email verification pending' }}</p><form method="POST" action="{{ route('profile.update') }}">@csrf @method('PATCH')<input name="name" value="{{ old('name', $user->name) }}" required><input name="email" type="email" value="{{ old('email', $user->email) }}" required><input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Phone"><button class="button button-teal">Save profile</button></form></section>
<section class="dashboard-card"><h2>Upcoming appointments</h2>@forelse($upcomingAppointments as $appointment)<div class="record-row"><div><strong>{{ $appointment->service }}</strong><small>{{ $appointment->starts_at->format('M j, Y · g:i A') }} · {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</small>@if($appointment->reason)<small>Reason: {{ $appointment->reason }}</small>@endif</div>@if(!in_array($appointment->status, ['cancelled','rejected']))<form method="POST" action="{{ route('appointments.cancel', $appointment) }}">@csrf @method('DELETE')<input name="reason" required placeholder="Reason" aria-label="Cancellation reason"><button class="text-button">Cancel</button></form>@endif</div>@empty<p class="empty-state">No upcoming appointments.</p>@endforelse</section>
<section class="dashboard-card"><h2>Prescription history</h2>@forelse($prescriptions as $prescription)<div class="record-row"><div><strong>{{ $prescription->eye }} · Sphere {{ $prescription->sphere ?? '—' }}</strong><small>Issued {{ $prescription->issued_at->format('M j, Y') }} · Cylinder {{ $prescription->cylinder ?? '—' }} · Axis {{ $prescription->axis ?? '—' }}</small></div></div>@empty<p class="empty-state">No prescriptions on file.</p>@endforelse</section>
<section class="dashboard-card"><h2>Previous visits</h2>@forelse($pastAppointments as $appointment)<div class="record-row"><div><strong>{{ $appointment->service }}</strong><small>{{ $appointment->starts_at->format('M j, Y') }} · {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</small></div></div>@empty<p class="empty-state">No previous appointments.</p>@endforelse</section>
</div></main><script>
document.querySelector('[data-slot-date]')?.addEventListener('change', async (event) => {
 const select=document.querySelector('[data-slot-select]'), error=document.querySelector('[data-slot-error]');
 select.disabled=true; select.innerHTML='<option>Loading times…</option>'; error.hidden=true;
 try { const response=await fetch('/appointment-slots?date='+encodeURIComponent(event.target.value),{headers:{Accept:'application/json'}});
  const data=await response.json(); if(!response.ok) throw new Error(data.message||'Unable to load times.');
  select.innerHTML=data.data.length?'<option value="">Choose a time</option>'+data.data.map(slot=>`<option value="${slot.value}">${slot.label}</option>`).join(''):'<option>No times available</option>'; select.disabled=!data.data.length;
 } catch (e) { select.innerHTML='<option>Unable to load times</option>'; error.textContent=e.message; error.hidden=false; }
});
</script></body></html>
