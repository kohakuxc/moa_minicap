const modal = (name, open) => {
    const element = document.querySelector(`[data-modal="${name}"]`);
    if (!element) return;
    if (open) {
        document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
            if (backdrop !== element) backdrop.hidden = true;
        });
    }
    element.hidden = !open;
    document.body.classList.toggle("modal-open", open);
};

const siteHeader = document.querySelector(".site-header");
const menuToggle = document.querySelector("[data-menu-toggle]");
const closeMenu = () => {
    siteHeader?.classList.remove("menu-open");
    menuToggle?.setAttribute("aria-expanded", "false");
    menuToggle?.setAttribute("aria-label", "Open menu");
};

menuToggle?.addEventListener("click", () => {
    const isOpen = siteHeader?.classList.toggle("menu-open") ?? false;
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    menuToggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
});
siteHeader?.querySelectorAll(".main-nav a, .header-actions button").forEach((item) =>
    item.addEventListener("click", closeMenu),
);
document.addEventListener("click", (event) => {
    if (siteHeader?.classList.contains("menu-open") && !siteHeader.contains(event.target)) {
        closeMenu();
    }
});

document
    .querySelectorAll("[data-open-booking]")
    .forEach((button) =>
        button.addEventListener("click", () => modal("booking", true)),
    );
document
    .querySelectorAll("[data-open-auth]")
    .forEach((button) =>
        button.addEventListener("click", () => modal("auth", true)),
    );
document.querySelectorAll("[data-auth-switch]").forEach((button) =>
    button.addEventListener("click", () => {
        document.querySelectorAll("[data-auth-view]").forEach((view) => {
            view.hidden = view.dataset.authView !== button.dataset.authSwitch;
        });
    }),
);
document.querySelectorAll("[data-auth-form]").forEach((form) =>
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const type = form.dataset.authForm;
        const submit = form.querySelector('button[type="submit"]');
        const error = document.querySelector(`[data-auth-error="${type}"]`);
        const payload = Object.fromEntries(new FormData(form).entries());
        const defaultLabel = submit.textContent;
        error.hidden = true;
        submit.disabled = true;
        if (type === "login") {
            submit.innerHTML =
                '<span class="lds-ellipsis" role="status" aria-label="Logging in"><div></div><div></div><div></div><div></div></span>';
        }
        fetch(type === "register" ? "/auth/register" : "/auth/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
                "ngrok-skip-browser-warning": "true",
            },
            credentials: "same-origin",
            body: JSON.stringify(payload),
        })
            .then(async (response) => {
                const contentType = response.headers.get("content-type") || "";
                const data = contentType.includes("application/json")
                    ? await response.json()
                    : {
                          message:
                              "The server returned an unexpected response. Please refresh and try again.",
                      };
                if (!response.ok)
                    throw new Error(
                        Object.values(data.errors || {}).flat()[0] ||
                            data.message ||
                            "Unable to complete authentication.",
                    );
                if (type === "register") {
                    submit.textContent = "Account created";
                }
                window.location.assign(
                    type === "register"
                        ? "/email/verify"
                        : data.redirect ||
                              (data.user?.role === "admin"
                                  ? "/admin"
                                  : "/profile/create"),
                );
            })
            .catch((requestError) => {
                error.textContent = requestError.message;
                error.hidden = false;
                submit.textContent = defaultLabel;
                submit.disabled = false;
            });
    }),
);
document.querySelectorAll("[data-close-modal]").forEach((button) =>
    button.addEventListener("click", () => {
        const backdrop = button.closest(".modal-backdrop");
        if (backdrop) {
            document.querySelectorAll(".modal-backdrop").forEach((item) => {
                item.hidden = true;
            });
            document.body.classList.remove("modal-open");
        }
    }),
);
document.querySelectorAll(".modal-backdrop").forEach((backdrop) =>
    backdrop.addEventListener("click", (event) => {
        if (event.target === backdrop) modal(backdrop.dataset.modal, false);
    }),
);
document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    const openBackdrop = document.querySelector(
        ".modal-backdrop:not([hidden])",
    );
    if (openBackdrop) modal(openBackdrop.dataset.modal, false);
});

document.querySelectorAll(".filter").forEach((filter) =>
    filter.addEventListener("click", () => {
        document
            .querySelectorAll(".filter")
            .forEach((item) => item.classList.remove("active"));
        filter.classList.add("active");
        const selected = filter.dataset.filter;
        document.querySelectorAll(".product-card").forEach((card) => {
            card.hidden =
                selected !== "all" &&
                !card.dataset.category.split(" ").includes(selected);
        });
    }),
);
document.querySelectorAll(".heart").forEach((heart) =>
    heart.addEventListener("click", () => {
        heart.classList.toggle("saved");
        heart.textContent = heart.classList.contains("saved") ? "♥" : "♡";
    }),
);

let currentStep = 1;
const updateBookingStep = () => {
    document
        .querySelectorAll(".booking-step")
        .forEach((step) =>
            step.classList.toggle(
                "active-step",
                Number(step.dataset.step) === currentStep,
            ),
        );
    document
        .querySelectorAll(".step")
        .forEach((step, index) =>
            step.classList.toggle("active", index + 1 === currentStep),
        );
    const back = document.querySelector("[data-prev]");
    const next = document.querySelector("[data-next]");
    back.hidden = currentStep === 1;
    next.textContent = currentStep === 3 ? "Confirm booking" : "Continue";
};
document.querySelectorAll(".choice, .date, .time").forEach((choice) =>
    choice.addEventListener("click", () => {
        const reasonBox = document.querySelector("[data-date-reason]");
        if (choice.classList.contains("date")) {
            choice.parentElement
                .querySelectorAll("button")
                .forEach((item) => item.classList.remove("focused"));
            if (choice.classList.contains("unavailable")) {
                choice.classList.add("focused");
                if (reasonBox) {
                    reasonBox.textContent = choice.dataset.reason;
                    reasonBox.hidden = false;
                }
                return;
            }
            if (reasonBox) reasonBox.hidden = true;
        }
        choice.parentElement
            .querySelectorAll("button")
            .forEach((item) => item.classList.remove("selected"));
        choice.classList.add("selected");
    }),
);
document.querySelector(".date:not(.unavailable)")?.classList.add("selected");
const bookingPayload = () => {
    const form = document.querySelector("#booking-form");
    return {
        service: document.querySelector(".choice.selected")?.dataset.choice,
        starts_at: `${document.querySelector(".date.selected")?.dataset.date} ${document.querySelector(".time.selected")?.dataset.time}:00`,
    };
};
const replacePanel = document.querySelector("[data-replace-panel]");
const resetReplacePanel = () => {
    if (!replacePanel) return;
    replacePanel.hidden = true;
    replacePanel.querySelector("[data-replace-reason]").value = "";
    replacePanel.querySelector("[data-replace-agree]").checked = false;
    replacePanel.querySelector("[data-replace-error]").hidden = true;
};
const submitBooking = (form, extra = {}) =>
    fetch("/appointments", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            "ngrok-skip-browser-warning": "true",
        },
        credentials: "same-origin",
        body: JSON.stringify({ ...bookingPayload(), ...extra }),
    })
        .then(async (response) => {
            if (
                response.status === 401 ||
                response.status === 302 ||
                response.redirected
            ) {
                modal("booking", false);
                modal("auth", true);
                document.querySelectorAll("[data-auth-view]").forEach((view) => {
                    view.hidden = view.dataset.authView !== "login";
                });
                return;
            }
            const data = await response.json();
            if (response.status === 409 && data.code === "existing_appointment" && replacePanel) {
                // One-appointment policy: ask the patient to agree and give a reason.
                replacePanel.querySelector("[data-replace-policy]").textContent = data.message;
                replacePanel.querySelector("[data-replace-current]").textContent =
                    `${data.appointment.service} · ${data.appointment.when}`;
                form.hidden = true;
                document.querySelector(".stepper")?.setAttribute("hidden", "");
                replacePanel.hidden = false;
                return;
            }
            if (!response.ok)
                throw new Error(
                    Object.values(data.errors || {}).flat()[0] ||
                        data.message ||
                        "Unable to request appointment.",
                );
            form.hidden = true;
            resetReplacePanel();
            document.querySelector(".booking-success").hidden = false;
        })
        .catch((error) => window.alert(error.message));
document.querySelector("[data-replace-keep]")?.addEventListener("click", () => {
    resetReplacePanel();
    document.querySelector("#booking-form").hidden = false;
    document.querySelector(".stepper")?.removeAttribute("hidden");
});
document.querySelector("[data-replace-confirm]")?.addEventListener("click", () => {
    const reason = replacePanel.querySelector("[data-replace-reason]").value.trim();
    const error = replacePanel.querySelector("[data-replace-error]");
    if (!replacePanel.querySelector("[data-replace-agree]").checked) {
        error.textContent = "Please tick the box to confirm you agree to cancel your previous appointment.";
        error.hidden = false;
        return;
    }
    if (reason.length < 5) {
        error.textContent = "Please give a reason for cancelling (at least 5 characters).";
        error.hidden = false;
        return;
    }
    error.hidden = true;
    submitBooking(document.querySelector("#booking-form"), {
        replace_existing: true,
        cancellation_reason: reason,
    });
});
document.querySelector("[data-next]")?.addEventListener("click", () => {
    if (currentStep === 1 && !document.querySelector(".date.selected")) {
        const reasonBox = document.querySelector("[data-date-reason]");
        if (reasonBox) {
            reasonBox.textContent = "Please choose an available date to continue.";
            reasonBox.hidden = false;
        }
        return;
    }
    if (currentStep < 3) {
        currentStep += 1;
        updateBookingStep();
        return;
    }
    const form = document.querySelector("#booking-form");
    if (form.reportValidity()) submitBooking(form);
});
document.querySelector("[data-prev]")?.addEventListener("click", () => {
    currentStep -= 1;
    updateBookingStep();
});
document
    .querySelector('[data-modal="booking"] .modal-close')
    ?.addEventListener("click", () => {
        currentStep = 1;
        document.querySelector("#booking-form").hidden = false;
        document.querySelector(".booking-success").hidden = true;
        resetReplacePanel();
        document.querySelector(".stepper")?.removeAttribute("hidden");
        updateBookingStep();
    });
