<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f5ef">
    <meta name="description" content="Meet the people who make Tunisia feel like somewhere you belong.">
    <title>Ahla — Tunisia looks better through a local lens</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="social-app">
    <a class="skip-link" href="#discover">Skip to local discovery</a>

    <header class="social-header">
        <a class="social-brand" href="{{ route('discover') }}" aria-label="Ahla home">
            <span class="social-brand-flower" aria-hidden="true">✳</span>
            <span>ahla<span>.</span></span>
        </a>
        <nav class="social-nav" aria-label="Main navigation">
            <a class="social-nav-link is-active" href="#discover" data-view="discover"><span aria-hidden="true">◉</span> Discover</a>
            <a class="social-nav-link" href="#tunisia" data-scroll-to="tunisia"><span aria-hidden="true">⌖</span> Tunisia</a>
            <button class="social-nav-link nav-chat-button" type="button" data-open-chat><span aria-hidden="true">◌</span> Chats <i id="nav-unread" class="nav-unread">1</i></button>
            <button class="social-nav-link" type="button" data-view="profile"><span aria-hidden="true">◎</span> Profile</button>
        </nav>
        <div class="social-header-right">
            <span class="header-trip"><span class="trip-flag">🇹🇳</span> Your Tunisia era</span>
            <button class="header-avatar" type="button" data-view="profile" aria-label="Open your profile">SM</button>
        </div>
    </header>

    <main class="social-main" id="app-main">
        <section class="social-welcome" id="discover">
            <div class="welcome-copy">
                <p class="social-eyebrow"><span class="eyebrow-pulse"></span> YOUR PEOPLE ARE OUT THERE</p>
                <h1>Come for Tunisia.<br><em>Stay for the people.</em></h1>
                <p class="welcome-deck">The best way into a new place? Meet someone who already calls it home.</p>
            </div>
            <div class="welcome-stats">
                <div class="stat-avatars" aria-hidden="true">
                    <img src="{{ $locals[0]['photo'] }}" alt="">
                    <img src="{{ $locals[2]['photo'] }}" alt="">
                    <img src="{{ $locals[3]['photo'] }}" alt="">
                    <span>+</span>
                </div>
                <p><strong>People first,</strong><br>tourist stuff second.</p>
                <span class="welcome-spark" aria-hidden="true">✳</span>
            </div>
            <span class="welcome-sun" aria-hidden="true">☼</span>
        </section>

        <div class="social-layout">
            <section class="discover-column" aria-labelledby="locals-title">
                <div class="section-heading social-section-heading">
                    <div>
                        <p class="section-kicker">A LITTLE LOCAL MAGIC</p>
                        <h2 id="locals-title">People to know <span class="heading-count" id="local-count"></span></h2>
                    </div>
                    <button class="text-button" type="button" data-reset-filters>Surprise me <span aria-hidden="true">↗</span></button>
                </div>

                <div class="interest-filters" id="interest-filters" aria-label="Filter locals by interest">
                    <button class="interest-chip is-selected" type="button" data-interest="">✳ <span>For you</span></button>
                    <button class="interest-chip" type="button" data-interest="Photography">📷 <span>Photography</span></button>
                    <button class="interest-chip" type="button" data-interest="Food">🍋 <span>Food</span></button>
                    <button class="interest-chip" type="button" data-interest="Art">🎨 <span>Art</span></button>
                    <button class="interest-chip" type="button" data-interest="Nature">🌿 <span>Outdoors</span></button>
                </div>

                <div class="featured-wrap" id="featured-person" aria-live="polite"></div>
                <div class="deck-controls" aria-label="Choose what to do">
                    <button class="deck-action pass-action" type="button" data-pass-local aria-label="Pass on this person" title="Maybe later"><span aria-hidden="true">×</span></button>
                    <span class="deck-hint">Not your vibe? No worries.</span>
                    <button class="deck-action connect-action" type="button" data-connect-local aria-label="Say ahla and connect" title="Say ahla"><span aria-hidden="true">♡</span></button>
                </div>

                <div class="people-next">
                    <div class="mini-section-heading">
                        <h3>More good humans <span aria-hidden="true">✳</span></h3>
                        <span>Tap a face to meet them</span>
                    </div>
                    <div class="people-strip" id="people-strip"></div>
                </div>
            </section>

            <aside class="social-sidebar">
                <section class="journey-card" aria-label="How Ahla works">
                    <div class="journey-top">
                        <span class="journey-stamp">AHLA<br><b>اهلا</b></span>
                        <div><p class="section-kicker">A SMALL HELLO GOES FAR</p><h2>Find your<br>kind of Tunisia.</h2></div>
                    </div>
                    <div class="journey-steps">
                        <span class="journey-step is-done"><i>1</i> Discover</span>
                        <span class="journey-dash"></span>
                        <span class="journey-step"><i>2</i> Say ahla</span>
                        <span class="journey-dash"></span>
                        <span class="journey-step"><i>3</i> Go explore</span>
                    </div>
                    <p class="journey-note">New city, familiar faces. Your adventure starts with a hello.</p>
                </section>

                <section class="connections-card" aria-labelledby="connections-title">
                    <div class="mini-section-heading connection-heading">
                        <h3 id="connections-title">Your people <span class="connection-count" id="connection-count">1</span></h3>
                        <button class="subtle-button" type="button" data-open-chat>See chats <span aria-hidden="true">↗</span></button>
                    </div>
                    <div class="incoming-request" id="incoming-request"></div>
                    <div class="connection-list" id="connection-list"></div>
                    <div class="chat-nudge"><span aria-hidden="true">💬</span><span>A new connection is a good excuse to say hi.</span></div>
                </section>

                <section class="postcard-card">
                    <div class="postcard-pattern" aria-hidden="true">✳ &nbsp; ✳ &nbsp; ✳</div>
                    <p class="postcard-label">A NOTE FROM TUNIS</p>
                    <p class="postcard-quote">“The stranger you meet today might be the reason you come back.”</p>
                    <span class="postcard-signature">— someone’s teta, probably</span>
                </section>
            </aside>
        </div>

        <section class="tunisia-section" id="tunisia" aria-labelledby="tunisia-title">
            <div class="map-intro">
                <div>
                    <p class="social-eyebrow"><span class="eyebrow-pulse"></span> YOUR NEXT STORY IS SOMEWHERE HERE</p>
                    <h2 id="tunisia-title">A whole country<br>of <em>hellos.</em></h2>
                    <p>Pick a place. Meet the people who make it theirs.</p>
                </div>
                <div class="map-legend"><span class="legend-dot"></span> Locals are around</div>
            </div>
            <div class="map-explorer">
                <div class="tunisia-map-card">
                    <div class="map-texture" aria-hidden="true"></div>
                    <div class="map-region-label map-north-label">THE NORTH</div>
                    <svg class="tunisia-svg" viewBox="0 0 365 670" role="img" aria-label="Map of Tunisia based on Natural Earth geography, with clickable city markers">
                    <defs>
                        <pattern id="map-hatch" width="12" height="12" patternTransform="rotate(45)" patternUnits="userSpaceOnUse">
                            <line x1="0" x2="0" y1="0" y2="14" stroke="#719687" stroke-opacity=".12" stroke-width="2"/>
                        </pattern>
                        <filter id="map-shadow" height="140%" width="140%" x="-20%" y="-20%">
                            <feDropShadow dx="0" dy="12" flood-color="#3d6555" flood-opacity=".16" stdDeviation="10"/>
                        </filter>
                    </defs>
                    <path class="tunisia-land-shadow" d="M340.4,388.1 L336.7,414.2 L336.3,437.7 L342.7,448.5 L342.9,453.3 L340.4,458.7 L286.1,489.4 L281.7,494.9 L268.7,503.3 L263.5,515 L258.1,521.1 L242,525.8 L229.2,546.1 L240.6,594.2 L240.5,601.2 L237.3,608.7 L211.6,645.2 L181.5,659.7 L143.5,490.1 L86.7,446.8 L84.3,432.7 L76.9,411.5 L69,399.7 L41,383.4 L38.5,380.1 L36.7,371.5 L22.7,338.8 L20,328.2 L21.1,305.4 L39.9,289.4 L47.1,275.1 L69.9,260.9 L79.6,245.3 L82.1,222.7 L85,213 L91.5,202 L85.3,183.7 L85.4,160.5 L79.8,147 L87.9,94.9 L84.2,86.9 L76.7,83.2 L76.6,81.1 L89.6,70.6 L95.5,58.8 L108.1,52.1 L106.1,42.6 L125.9,37 L151.4,18.9 L195,5.5 L207.1,8.4 L202.7,17.3 L206.4,24.3 L211.7,20.1 L210,13.4 L227,13.7 L235.7,17.9 L235.1,33.7 L246.7,49.2 L243.5,56.9 L253,61.5 L261.5,56 L265.7,47.9 L281.3,43.2 L296.1,31.3 L304.3,30.1 L310.1,48.4 L304.5,51.4 L297.4,60.4 L283.9,83.4 L271.4,90.2 L262.1,99.1 L258.1,112.7 L260.5,125.8 L267.3,139.2 L275.1,147.2 L282.7,149.8 L300.3,162.5 L303.5,190 L309.6,198.7 L289.3,231.4 L275.3,250.4 L262.8,262.7 L229.5,287 L223.2,299.9 L223.9,307.6 L232.7,326.6 L244.4,337.8 L256.3,343.8 L277.1,341.4 L277.8,357.5 L291.9,355.7 L296.6,347.2 L306.8,353 L312,370.8 L320.6,376.4 L321.6,378.5 L316.2,381.9 L327,385.5 L332,384.1 L340.4,388.1Z"/>
                    <path class="tunisia-land" d="M340.4,388.1 L336.7,414.2 L336.3,437.7 L342.7,448.5 L342.9,453.3 L340.4,458.7 L286.1,489.4 L281.7,494.9 L268.7,503.3 L263.5,515 L258.1,521.1 L242,525.8 L229.2,546.1 L240.6,594.2 L240.5,601.2 L237.3,608.7 L211.6,645.2 L181.5,659.7 L143.5,490.1 L86.7,446.8 L84.3,432.7 L76.9,411.5 L69,399.7 L41,383.4 L38.5,380.1 L36.7,371.5 L22.7,338.8 L20,328.2 L21.1,305.4 L39.9,289.4 L47.1,275.1 L69.9,260.9 L79.6,245.3 L82.1,222.7 L85,213 L91.5,202 L85.3,183.7 L85.4,160.5 L79.8,147 L87.9,94.9 L84.2,86.9 L76.7,83.2 L76.6,81.1 L89.6,70.6 L95.5,58.8 L108.1,52.1 L106.1,42.6 L125.9,37 L151.4,18.9 L195,5.5 L207.1,8.4 L202.7,17.3 L206.4,24.3 L211.7,20.1 L210,13.4 L227,13.7 L235.7,17.9 L235.1,33.7 L246.7,49.2 L243.5,56.9 L253,61.5 L261.5,56 L265.7,47.9 L281.3,43.2 L296.1,31.3 L304.3,30.1 L310.1,48.4 L304.5,51.4 L297.4,60.4 L283.9,83.4 L271.4,90.2 L262.1,99.1 L258.1,112.7 L260.5,125.8 L267.3,139.2 L275.1,147.2 L282.7,149.8 L300.3,162.5 L303.5,190 L309.6,198.7 L289.3,231.4 L275.3,250.4 L262.8,262.7 L229.5,287 L223.2,299.9 L223.9,307.6 L232.7,326.6 L244.4,337.8 L256.3,343.8 L277.1,341.4 L277.8,357.5 L291.9,355.7 L296.6,347.2 L306.8,353 L312,370.8 L320.6,376.4 L321.6,378.5 L316.2,381.9 L327,385.5 L332,384.1 L340.4,388.1Z" fill="url(#map-hatch)"/>
                    <path class="tunisia-island" d="M322.2,243.4 L309.9,250.1 L312.2,244.3 L320.4,237.3 L322.5,239 L322.2,243.4Z"/>
                    <path class="tunisia-island" d="M296.6,338.4 L288.6,341.6 L277.8,336.8 L279.6,323 L293.8,322.6 L301.4,329.1 L303,332.6 L296.6,338.4Z"/>
                    <text class="map-sea-label" x="292" y="283">GULF OF HAMMAMET</text>
                    <text class="map-sea-label" x="290" y="450">GULF OF GABES</text>
                    <g id="map-markers" aria-label="Cities with locals nearby"></g>
                    <text class="map-country-label" x="126" y="402" transform="rotate(78 126 402)">T U N I S I A</text>
                    <text class="map-scale-label" x="13" y="650">NORTH ↑</text>
                    <path class="map-compass" d="m27 620 7-20 7 20-7-4z"/>
                    </svg>
                    <div class="map-coordinates">36° 48′ N &nbsp; 10° 11′ E <span>✳</span></div>
                    <span class="map-attribution">Country outline: Natural Earth · Public domain</span>
                </div>
                <div class="map-selection-card" id="map-selection-card">
                    <span class="map-selected-kicker">A PLACE TO START</span>
                    <h3>Pick a pin <span aria-hidden="true">↗</span></h3>
                    <p>See who you might meet there.</p>
                </div>
            </div>
            <div class="city-pills" id="city-pills" aria-label="Explore cities"></div>
        </section>

        <section class="experience-social-section" id="experiences" aria-labelledby="experiences-title">
            <div class="section-heading social-section-heading">
                <div>
                    <p class="section-kicker">THE CITY IS BETTER WITH A LOCAL</p>
                    <h2 id="experiences-title">Plans worth making <span aria-hidden="true">✳</span></h2>
                </div>
                <a class="text-button" href="#tunisia">Find a place first <span aria-hidden="true">↗</span></a>
            </div>
            <div class="experience-social-grid">
                @foreach ($experiences->take(3) as $experience)
                    <article class="experience-social-card">
                        <img src="{{ $experience->photo_url }}" alt="{{ $experience->title }}" loading="lazy" width="640" height="430">
                        <div class="experience-social-content">
                            <span class="experience-place"><i></i>{{ $experience->host_city }}</span>
                            <h3>{{ $experience->title }}</h3>
                            <p>{{ $experience->description }}</p>
                            <div><span class="experience-host">A day with <strong>{{ $experience->host_name }}</strong></span><span class="experience-price">{{ $experience->price_per_person }} <small>TND / person</small></span></div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="experience-footnote"><span>✳</span> Meet your local first. Make a plan together if the vibes are right.</p>
        </section>
    </main>

    <main class="user-profile-screen" id="profile-screen" hidden>
        <div class="profile-screen-heading">
            <button class="profile-back-button" type="button" data-view="discover">← <span>Back to Ahla</span></button>
            <span class="profile-screen-kicker">YOUR AHLA PROFILE</span>
        </div>
        <div id="user-profile-content"></div>
    </main>

    <footer class="social-footer">
        <a class="social-brand" href="{{ route('discover') }}"><span class="social-brand-flower">✳</span><span>ahla<span>.</span></span></a>
        <p>Made with love in Tunisia. <span>أهلاً وسهلاً — you’re always welcome.</span></p>
        <span class="footer-endmark">YALLA, LET’S GO <b>↗</b></span>
    </footer>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <a class="mobile-nav-item is-active" href="#discover" data-view="discover"><span aria-hidden="true">◉</span><small>Explore</small></a>
        <a class="mobile-nav-item" href="#tunisia" data-scroll-to="tunisia"><span aria-hidden="true">⌖</span><small>Map</small></a>
        <button class="mobile-nav-item" type="button" data-open-chat><span aria-hidden="true">◌<i id="mobile-nav-unread"></i></span><small>Chats</small></button>
        <button class="mobile-nav-item" type="button" data-view="profile"><span aria-hidden="true">◎</span><small>Profile</small></button>
    </nav>

    <dialog class="profile-dialog" id="profile-dialog" aria-labelledby="profile-name"></dialog>
    <dialog class="edit-profile-dialog" id="edit-profile-dialog" aria-labelledby="edit-profile-title">
        <form class="edit-profile-form" data-edit-profile-form>
            <div class="edit-profile-heading"><div><p class="section-kicker">MAKE IT YOURS</p><h2 id="edit-profile-title">Edit your profile</h2></div><button class="dialog-close" type="button" data-close-edit-profile aria-label="Close">×</button></div>
            <label>Name<input name="name" type="text" maxlength="60" required></label>
            <label>Visiting<input name="location" type="text" maxlength="80" required></label>
            <label>About you<textarea name="bio" rows="4" maxlength="240" required></textarea></label>
            <label>Interests <small>Separate each with a comma</small><input name="interests" type="text" maxlength="160" required></label>
            <div class="edit-profile-actions"><button type="button" data-close-edit-profile>Cancel</button><button type="submit">Save changes <span>↗</span></button></div>
        </form>
    </dialog>
    <dialog class="chat-dialog" id="chat-dialog" aria-labelledby="chat-title">
        <div class="chat-app" id="chat-app"></div>
    </dialog>
    <div class="match-toast" id="match-toast" role="status" aria-live="polite"></div>

    <script>
        window.ahlaDemo = {
            locals: @json($locals),
            places: @json($places),
            conversations: @json($conversations),
            profile: {
                name: 'Sarah Martin',
                location: 'Visiting Tunis, Tunisia',
                bio: 'Curious traveller collecting little stories, good coffee and local recommendations across Tunisia.',
                interests: ['Photography', 'Food', 'Slow travel'],
                photo: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=85'
            },
            copy: {
                verified: 'Local, verified',
                viewProfile: 'Meet the person',
                localStory: 'A little local story',
                interests: 'Things I love',
                languages: 'We can chat in',
                whatToDo: 'If you are around',
                connect: 'Say ahla',
                connected: 'You found your person!',
                connectedCopy: 'You and {name} are connected. Go say hi!',
                chatNow: 'Say hi',
                online: 'Around now',
                offline: 'Usually around',
                incoming: 'WANTS TO SAY AHLA',
                accept: 'Say ahla back',
                later: 'Maybe later',
                emptyTitle: 'That’s everyone for now',
                emptyCopy: 'Pick another place or clear your filters for more locals.',
                reset: 'Show me everyone',
                messagePlaceholder: 'Write a little hello…',
                messageSend: 'Send',
                newMessage: 'Just now',
                chatEmpty: 'Your next good conversation starts here.',
                connectionAdded: 'You’re connected',
                requestAccepted: 'You both said ahla!',
                passed: 'We’ll save that hello for another time.',
                locals: 'locals',
                experiences: 'experiences',
                km: 'people nearby',
                close: 'Close',
                back: 'Back to discovery',
                profile: 'LOCAL PROFILE',
                matchPercent: 'YOUR VIBE CHECK',
                demoNote: 'Demo mode · Say ahla to connect instantly',
                chatTitle: 'Your conversations',
                searchChats: 'Find a friend',
                chats: 'CHATS',
                requests: 'REQUESTS',
                noChats: 'Your people will show up here.',
                mapLocals: '{count} locals',
                mapExperiences: '{count} little adventures',
                mapPrompt: 'Tap a pin to meet someone around here.',
                seeLocals: 'Meet people here',
                citySelected: 'LOCAL LIFE IN',
                mapPromptTitle: 'Tunisia is full of little worlds',
                chatIntro: 'A new friend is a great reason to start with hello.',
                startChat: 'Start a conversation',
                chatToday: 'TODAY',
                messageMe: 'You',
                connectionLabel: 'YOUR PEOPLE',
                unread: 'unread',
                typing: 'is typing…',
                messageFailed: 'Could not save your message. Please try again.',
                requestDismissed: 'Request saved for later.',
                gotIt: 'Got it',
                seeProfile: 'View profile'
            }
        };
    </script>
</body>
</html>
