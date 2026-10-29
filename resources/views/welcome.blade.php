<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MOA — Precision vision, signature style</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
    <script type="module" src="/app.js" defer></script>
</head>
<body>
    <div class="app-shell">
        <header class="site-header">
            <a class="brand" href="#home" aria-label="MOA home">
                <span class="brand-mark"><i></i><i></i></span>
                <span>MOA</span>
            </a>
            <nav class="main-nav" aria-label="Main navigation">
                <a class="active" href="#home">Home</a>
                <a href="#catalog">Catalog</a>
                <a href="#services">Services</a>
                <a href="#about">Our story</a>
            </nav>
            <div class="header-actions">
                <button class="text-button" data-open-auth>Log in</button>
                <button class="button button-teal button-small" data-open-booking>Book an appointment <span>↗</span></button>
            </div>
            <button class="menu-button" aria-label="Toggle menu">☰</button>
        </header>

        <main>
            <section class="hero" id="home">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="eyebrow-line"></span> The Cabagte Studio· Est. 6767</div>
                    <h1>Your Vision and<br><em>Eye Care Specialist</em></h1>
                    <p class="hero-text">et al et al et al et al et al et al et al et al et al et al et al et al</p>
                    <div class="hero-actions">
                        <button class="button button-dark" data-open-booking>Find your frames <span>↗</span></button>
                        <a class="play-link" href="#about"><span class="play-icon">▶</span> Why MOA?</a>
                    </div>
                    <div class="hero-proof">
                        <div class="avatar-stack"><span>AM</span><span>JS</span><span>RK</span><b>+</b></div>
                        <div><strong>4.9 / 5</strong><small>Loved by 6767+ patients</small></div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-orb"></div>
                    <div class="hero-image">
                        <img src="https://images.unsplash.com/photo-1574258495973-f010dfbb5371?auto=format&fit=crop&w=1000&q=85" alt="Woman wearing stylish translucent glasses">
                    </div>
                    <div class="floating-card card-top"><span class="mini-icon">✦</span><div><b>Thoughtfully<br>selected</b><small>Frames starts at ₱1</small></div></div>
                    <div class="floating-card card-bottom"><span class="trend-line">↗</span><div><b>98%</b><small>Patient satisfaction</small></div></div>
                    <div class="circle-label">MOA<br><span>CLINIC</span></div>
                </div>
            </section>

            <section class="trusted-row">
                <span>AS SEEN IN</span><b>VOGUE</b><b>monocle</b><b>GQ</b><b>hypebeast</b><b>THE CUT</b>
            </section>

            <section class="section catalog-section" id="catalog">
                <div class="section-heading">
                    <div><div class="eyebrow"><span class="eyebrow-line"></span> The collection</div><h2>Find your <em>style</em></h2></div>
                    <a href="#catalog" class="underlined-link">View all frames <span>↗</span></a>
                </div>
                <div class="filter-row" role="tablist" aria-label="Filter frames">
                    <button class="filter active" data-filter="all">All frames <span>24</span></button>
                    <button class="filter" data-filter="optical">Optical <span>12</span></button>
                    <button class="filter" data-filter="sun">Sunglasses <span>8</span></button>
                    <button class="filter" data-filter="new">New arrivals <span>4</span></button>
                </div>
                <div class="product-grid">
                    <article class="product-card" data-category="optical new">
                        <div class="product-image peach"><span class="product-badge">New</span><button aria-label="Save frame" class="heart">♡</button><img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=700&q=80" alt="Clear acetate eyewear"></div>
                        <div class="product-info"><div><h3>Haze 01</h3><p>Honey crystal · Optical</p></div><strong>$180</strong></div>
                    </article>
                    <article class="product-card" data-category="sun">
                        <div class="product-image sand"><button aria-label="Save frame" class="heart">♡</button><img src="https://images.unsplash.com/photo-1510172951991-856a654063f9?auto=format&fit=crop&w=700&q=80" alt="Black round sunglasses"></div>
                        <div class="product-info"><div><h3>Sol 02</h3><p>Ink black · Sunglasses</p></div><strong>$210</strong></div>
                    </article>
                    <article class="product-card" data-category="optical">
                        <div class="product-image lavender"><button aria-label="Save frame" class="heart">♡</button><img src="https://images.unsplash.com/photo-1577803645773-f96470509666?auto=format&fit=crop&w=700&q=80" alt="Tortoiseshell glasses"></div>
                        <div class="product-info"><div><h3>Form 03</h3><p>Dark tortoise · Optical</p></div><strong>$195</strong></div>
                    </article>
                    <article class="product-card" data-category="sun new">
                        <div class="product-image blue"><span class="product-badge">New</span><button aria-label="Save frame" class="heart">♡</button><img src="https://images.unsplash.com/photo-1508296695146-257a814070b4?auto=format&fit=crop&w=700&q=80" alt="Modern sunglasses"></div>
                        <div class="product-info"><div><h3>Arc 04</h3><p>Tea green · Sunglasses</p></div><strong>$225</strong></div>
                    </article>
                </div>
            </section>

            <section class="experience-section" id="services">
                <div class="experience-image"><img src="https://images.unsplash.com/photo-1625591340248-3f3e5e1a5b42?auto=format&fit=crop&w=1000&q=85" alt="Eye examination at MOA"></div>
                <div class="experience-copy"><div class="eyebrow"><span class="eyebrow-line"></span> More than a check-up</div><h2>Clarity is a<br><em>beautiful thing.</em></h2><p>From your first eye exam to the frame you never want to take off, we make every part of your eye care feel effortless.</p><div class="service-list"><div><span>01</span><strong>Comprehensive eye exams</strong><small>Clear answers, not just a prescription.</small></div><div><span>02</span><strong>Personal styling</strong><small>Find frames that feel like you.</small></div><div><span>03</span><strong>Contact lens fitting</strong><small>Comfortable vision, all day long.</small></div></div><button class="button button-outline" data-open-booking>Explore our services <span>↗</span></button></div>
            </section>

            <section class="quote-section" id="about">
                <div class="quote-mark">“</div><blockquote>MOA made eye care feel less clinical and a lot more <em>like me.</em></blockquote><div class="quote-author"><span class="author-avatar">JM</span><div><strong>Julia Martinez</strong><small>MOA patient since 2019</small></div><span class="quote-arrows">← &nbsp; 01 / 03 &nbsp; →</span></div>
            </section>
        </main>
        <footer><a class="brand" href="#home"><span class="brand-mark"><i></i><i></i></span><span>MOA</span></a><span>© {{ date('Y') }} MOA Studio. See beautifully.</span><span>Brooklyn · New York</span></footer>
    </div>

    <div class="modal-backdrop" data-modal="booking" hidden>
        <div class="modal booking-modal" role="dialog" aria-modal="true" aria-labelledby="booking-title">
            <button class="modal-close" data-close-modal>×</button>
            <div class="modal-kicker">MOA APPOINTMENTS</div><h2 id="booking-title">Let’s find your<br><em>clearer day.</em></h2>
            <div class="stepper"><span class="step active">01 <b>Service</b></span><span class="step">02 <b>Time</b></span><span class="step">03 <b>Your details</b></span></div>
            <form id="booking-form">
                <div class="booking-step active-step" data-step="1"><label>What can we help with?</label><div class="choice-grid"><button type="button" class="choice selected" data-choice="Comprehensive eye exam"><b>◉</b><span><strong>Eye exam</strong><small>45 min · $85</small></span></button><button type="button" class="choice" data-choice="Frame styling"><b>✧</b><span><strong>Frame styling</strong><small>30 min · Complimentary</small></span></button><button type="button" class="choice" data-choice="Contact lens fitting"><b>◌</b><span><strong>Contact lenses</strong><small>30 min · $45</small></span></button></div></div>
                <div class="booking-step" data-step="2"><label>Choose a time</label><div class="date-options"><button type="button" class="date selected" data-date="2026-11-24"><b>Tue</b><strong>24</strong><small>Nov</small></button><button type="button" class="date" data-date="2026-11-25"><b>Wed</b><strong>25</strong><small>Nov</small></button><button type="button" class="date" data-date="2026-11-26"><b>Thu</b><strong>26</strong><small>Nov</small></button><button type="button" class="date" data-date="2026-11-27"><b>Fri</b><strong>27</strong><small>Nov</small></button></div><div class="times"><button type="button" class="time selected" data-time="09:00">9:00 AM</button><button type="button" class="time" data-time="10:30">10:30 AM</button><button type="button" class="time" data-time="13:00">1:00 PM</button><button type="button" class="time" data-time="15:30">3:30 PM</button></div></div>
                <div class="booking-step" data-step="3"><label for="booking-name">Your details</label><input id="booking-name" required placeholder="Full name"><input type="email" required placeholder="Email address"><input type="tel" required placeholder="Phone number"><label class="check-row"><input type="checkbox" required> I agree to receive appointment reminders.</label></div>
                <div class="form-actions"><button type="button" class="button button-quiet" data-prev hidden>Back</button><button type="button" class="button button-teal" data-next>Continue <span>→</span></button></div>
            </form>
            <div class="booking-success" hidden><div class="success-icon">✓</div><h3>You’re all booked.</h3><p>We’ll see you Tuesday, June 24 at 9:00 AM. A confirmation is on its way to your inbox.</p><button class="button button-dark" data-close-modal>Done</button></div>
        </div>
    </div>
    <div class="modal-backdrop" data-modal="auth" hidden>
        <div class="modal auth-modal">
            <button class="modal-close" data-close-modal aria-label="Close authentication dialog">×</button>
            <div class="auth-view" data-auth-view="login">
                <div class="modal-kicker">WELCOME BACK</div>
                <h2>Your vision<br>is our mission eme.</h2>
                <p class="modal-intro">blah blah blah ang qt ni aya.</p>
                <form data-auth-form="login">
                    <input type="email" name="email" required placeholder="Email address">
                    <input type="password" name="password" required placeholder="Password">
                    <p class="auth-error" data-auth-error="login" role="alert" hidden></p>
                    <button type="submit" class="button button-teal">Log in <span>→</span></button>
                </form>
                <p class="form-foot">New to bomboclat? <button type="button" class="auth-link" data-auth-switch="register">Create an account</button></p>
            </div>
            <div class="auth-view" data-auth-view="register" hidden>
                <div class="modal-kicker">JOIN OCULO</div>
                <h2>Make room for<br><em>clearer days.</em></h2>
                <p class="modal-intro">Create your patient account to manage appointments, prescriptions, and saved frames.</p>
                <form data-auth-form="register">
                    <input type="text" name="name" required placeholder="Full name">
                    <input type="email" name="email" required placeholder="Email address">
                    <input type="tel" name="phone" required placeholder="Phone number">
                    <input type="password" name="password" required minlength="8" placeholder="Password (8+ characters)">
                    <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirm password">
                    <p class="auth-error" data-auth-error="register" role="alert" hidden></p>
                    <button type="submit" class="button button-teal">Create account <span>→</span></button>
                </form>
                <p class="form-foot">Already have an account? <button type="button" class="auth-link" data-auth-switch="login">Log in</button></p>
            </div>
        </div>
    </div>
</body>
</html>
