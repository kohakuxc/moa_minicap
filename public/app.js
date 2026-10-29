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
    const submit = form.querySelector('button[type="submit"]');
    submit.textContent = form.dataset.authForm === 'register' ? 'Account created ✓' : 'Logged in ✓';
    submit.disabled = true;
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
        form.hidden = true;
        document.querySelector('.booking-success').hidden = false;
    }
});
document.querySelector('[data-prev]')?.addEventListener('click', () => { currentStep -= 1; updateBookingStep(); });
document.querySelector('[data-modal="booking"] .modal-close')?.addEventListener('click', () => {
    currentStep = 1;
    document.querySelector('#booking-form').hidden = false;
    document.querySelector('.booking-success').hidden = true;
    updateBookingStep();
});
