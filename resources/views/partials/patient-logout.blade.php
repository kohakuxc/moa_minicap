    <div class="modal-backdrop logout-modal-backdrop" data-logout-modal hidden>
        <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title"
            aria-describedby="logout-description">
            <button class="modal-close" type="button" data-close-logout aria-label="Close logout dialog">×</button>
            <h2 id="logout-title">Leave the portal?</h2>
            <p id="logout-description">You will need to sign in again to manage your appointments and care history.</p>
            <div class="logout-actions">
                <button class="button button-quiet" type="button" data-close-logout>Stay signed in</button>
                <form method="POST" action="{{ route('auth.logout', [], false) }}">@csrf<button
                        class="button button-dark" type="submit">Log out</button></form>
            </div>
        </section>
    </div>
    <script>
        @if($bindMenu ?? true)const topbar = document.querySelector('[data-topbar]'); const menuToggle = document.querySelector('[data-menu-toggle]'); menuToggle?.addEventListener('click', () => { const open = topbar.classList.toggle('menu-open'); menuToggle.setAttribute('aria-expanded', open); });@endif
        const accountMenu = document.querySelector('[data-account-menu]'); document.addEventListener('click', (event) => { if (accountMenu && !accountMenu.contains(event.target)) accountMenu.removeAttribute('open'); });
        const logoutModal = document.querySelector('[data-logout-modal]'); const logoutTrigger = document.querySelector('[data-open-logout]'); const closeLogout = () => { logoutModal.hidden = true; logoutTrigger?.focus(); }; logoutTrigger?.addEventListener('click', () => { accountMenu?.removeAttribute('open'); logoutModal.hidden = false; logoutModal.querySelector('[data-close-logout]')?.focus(); }); logoutModal?.querySelectorAll('[data-close-logout]').forEach((button) => button.addEventListener('click', closeLogout)); logoutModal?.addEventListener('click', (event) => { if (event.target === logoutModal) closeLogout(); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { if (logoutModal && !logoutModal.hidden) closeLogout(); else accountMenu?.removeAttribute('open'); } });
    </script>
