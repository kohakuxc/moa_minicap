import './bootstrap';

const modal = (name, open) => {
    const element = document.querySelector(`[data-modal="${name}"]`);
    if (!element) return;
    if (open) {
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
            if (backdrop !== element) backdrop.hidden = true;
        });
    }
    element.hidden = !open;
    document.body.classList.toggle('modal-open', open);
};

document.querySelectorAll('[data-open-booking]').forEach((button) => button.addEventListener('click', () => modal('booking', true)));
document.querySelectorAll('[data-open-auth]').forEach((button) => button.addEventListener('click', () => modal('auth', true)));
document.querySelectorAll('[data-auth-switch]').forEach((button) => button.addEventListener('click', () => {
    document.querySelectorAll('[data-auth-view]').forEach((view) => {
        view.hidden = view.dataset.authView !== button.dataset.authSwitch;
    });
}));
document.querySelectorAll('[data-auth-form]').forEach((form) => form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const type = form.dataset.authForm;
    const submit = form.querySelector('button[type="submit"]');
    const error = document.querySelector(`[data-auth-error="${type}"]`);
    const payload = Object.fromEntries(new FormData(form).entries());
    error.hidden = true;
    submit.disabled = true;
    fetch(type === 'register' ? '/auth/register' : '/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    }).then(async (response) => {
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to complete authentication.');
        submit.textContent = type === 'register' ? 'Account created ✓' : 'Logged in ✓';
    }).catch((requestError) => {
        error.textContent = requestError.message;
        error.hidden = false;
        submit.disabled = false;
    });
}));
document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => {
    const backdrop = button.closest('.modal-backdrop');
    if (backdrop) {
        document.querySelectorAll('.modal-backdrop').forEach((item) => { item.hidden = true; });
        document.body.classList.remove('modal-open');
    }
}));
document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.addEventListener('click', (event) => {
    if (event.target === backdrop) modal(backdrop.dataset.modal, false);
}));
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openBackdrop = document.querySelector('.modal-backdrop:not([hidden])');
    if (openBackdrop) modal(openBackdrop.dataset.modal, false);
});

document.querySelectorAll('.filter').forEach((filter) => filter.addEventListener('click', () => {
    document.querySelectorAll('.filter').forEach((item) => item.classList.remove('active'));
    filter.classList.add('active');
    const selected = filter.dataset.filter;
    document.querySelectorAll('.product-card').forEach((card) => {
        card.hidden = selected !== 'all' && !card.dataset.category.split(' ').includes(selected);
    });
}));
document.querySelectorAll('.heart').forEach((heart) => heart.addEventListener('click', () => {
    heart.classList.toggle('saved');
    heart.textContent = heart.classList.contains('saved') ? '♥' : '♡';
}));

let currentStep = 1;
const updateBookingStep = () => {
    document.querySelectorAll('.booking-step').forEach((step) => step.classList.toggle('active-step', Number(step.dataset.step) === currentStep));
    document.querySelectorAll('.step').forEach((step, index) => step.classList.toggle('active', index + 1 === currentStep));
    const back = document.querySelector('[data-prev]');
    const next = document.querySelector('[data-next]');
    back.hidden = currentStep === 1;
    next.textContent = currentStep === 3 ? 'Confirm booking  →' : 'Continue  →';
};
document.querySelectorAll('.choice, .date, .time').forEach((choice) => choice.addEventListener('click', () => {
    choice.parentElement.querySelectorAll('button').forEach((item) => item.classList.remove('selected'));
    choice.classList.add('selected');
}));
document.querySelector('[data-next]')?.addEventListener('click', () => {
    if (currentStep < 3) { currentStep += 1; updateBookingStep(); return; }
    const form = document.querySelector('#booking-form');
    if (form.reportValidity()) {
        const service = document.querySelector('.choice.selected')?.dataset.choice;
        const date = document.querySelector('.date.selected')?.dataset.date;
        const time = document.querySelector('.time.selected')?.dataset.time;
        fetch('/appointments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            credentials: 'same-origin',
            body: JSON.stringify({ service, starts_at: `${date} ${time}:00` }),
        }).then(async (response) => {
            const data = await response.json();
            if (response.status === 401 || response.status === 302) throw new Error('Please log in and verify your email before booking.');
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to request appointment.');
            form.hidden = true;
            document.querySelector('.booking-success').hidden = false;
        }).catch((error) => window.alert(error.message));
    }
});
document.querySelector('[data-prev]')?.addEventListener('click', () => { currentStep -= 1; updateBookingStep(); });
document.querySelector('[data-modal="booking"] .modal-close')?.addEventListener('click', () => {
    currentStep = 1;
    document.querySelector('#booking-form').hidden = false;
    document.querySelector('.booking-success').hidden = true;
    updateBookingStep();
});
