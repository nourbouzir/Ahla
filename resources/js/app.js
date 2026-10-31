import './bootstrap';

const demo = window.ahlaDemo;

if (demo) {
    const copy = demo.copy;
    const locals = demo.locals;
    const localById = new Map(locals.map((person) => [person.id, person]));
    const featured = document.querySelector('#featured-person');
    const peopleStrip = document.querySelector('#people-strip');
    const profileDialog = document.querySelector('#profile-dialog');
    const chatDialog = document.querySelector('#chat-dialog');
    const chatApp = document.querySelector('#chat-app');
    const toast = document.querySelector('#match-toast');
    const appMain = document.querySelector('#app-main');
    const profileScreen = document.querySelector('#profile-screen');
    const profileContent = document.querySelector('#user-profile-content');
    const editProfileDialog = document.querySelector('#edit-profile-dialog');
    const editProfileForm = document.querySelector('[data-edit-profile-form]');
    let activeInterest = '';
    let activeCity = '';
    let activeChat = '';
    let activeView = 'discover';
    let toastTimer;

    const defaultState = {
        passed: [],
        connections: ['rami'],
        requests: ['sami'],
        messages: Object.fromEntries(demo.conversations.map(({ id, messages }) => [id, messages])),
        profile: { ...demo.profile },
    };

    const safeReadState = () => {
        try {
            const stored = JSON.parse(localStorage.getItem('ahla-social-demo') || '{}');
            return {
                passed: Array.isArray(stored.passed) ? stored.passed.filter((id) => localById.has(id)) : defaultState.passed,
                connections: Array.isArray(stored.connections)
                    ? stored.connections.filter((id) => localById.has(id))
                    : defaultState.connections,
                requests: Array.isArray(stored.requests)
                    ? stored.requests.filter((id) => localById.has(id))
                    : defaultState.requests,
                messages: stored.messages && typeof stored.messages === 'object'
                    ? { ...defaultState.messages, ...stored.messages }
                    : { ...defaultState.messages },
                profile: stored.profile && typeof stored.profile === 'object'
                    ? {
                        ...defaultState.profile,
                        name: typeof stored.profile.name === 'string' ? stored.profile.name.slice(0, 60) : defaultState.profile.name,
                        location: typeof stored.profile.location === 'string' ? stored.profile.location.slice(0, 80) : defaultState.profile.location,
                        bio: typeof stored.profile.bio === 'string' ? stored.profile.bio.slice(0, 240) : defaultState.profile.bio,
                        interests: Array.isArray(stored.profile.interests)
                            ? stored.profile.interests.filter((interest) => typeof interest === 'string').slice(0, 8)
                            : defaultState.profile.interests,
                    }
                    : { ...defaultState.profile },
            };
        } catch {
            return structuredClone(defaultState);
        }
    };

    let state = safeReadState();

    const persist = () => {
        try {
            localStorage.setItem('ahla-social-demo', JSON.stringify(state));
        } catch {
            showToast('Your browser could not save this demo. You can still keep exploring.');
        }
    };

    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);

    const initials = (name) => name.split(' ').map((part) => part[0]).slice(0, 2).join('');
    const messageList = (id) => {
        if (!Array.isArray(state.messages[id])) {
            state.messages[id] = [];
        }

        return state.messages[id];
    };

    const candidates = () => locals.filter((person) => (
        !state.passed.includes(person.id)
        && !state.connections.includes(person.id)
        && (!activeInterest || person.interests.includes(activeInterest))
        && (!activeCity || person.city === activeCity)
    ));

    const showToast = (message) => {
        if (!toast) return;

        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.classList.add('is-visible');
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 3300);
    };

    const profileMarkup = (person, isFeatured = false) => `
        <article class="local-card${isFeatured ? ' local-card-featured' : ' local-card-mini'}">
            <div class="local-photo-wrap">
                <img class="local-photo" src="${escapeHtml(person.photo)}" alt="${escapeHtml(person.name)}" ${isFeatured ? 'fetchpriority="high"' : 'loading="lazy"'}>
                <div class="photo-vignette"></div>
                <span class="local-location-chip"><span>⌖</span> ${escapeHtml(person.city)}, Tunisia</span>
                <span class="local-presence${person.online ? ' is-online' : ''}"><i></i>${person.online ? copy.online : copy.offline}</span>
                <span class="local-photo-caption">${escapeHtml(person.tagline)}</span>
            </div>
            <div class="local-profile">
                <div class="profile-topline"><span class="profile-match"><span>✳</span> ${copy.verified}</span><span class="profile-distance">✦ &nbsp;${escapeHtml(person.distance)}</span></div>
                <h3>${escapeHtml(person.name)}<span class="profile-age">, ${person.age}</span><span class="profile-verified" title="Local profile" aria-label="Local profile">✓</span></h3>
                <p class="profile-role">${escapeHtml(person.role)} <span>·</span> ${escapeHtml(person.city)}</p>
                <p class="profile-bio">${escapeHtml(person.bio)}</p>
                <div class="profile-interests">${person.interests.map((interest) => `<button type="button" class="profile-interest" data-select-interest="${escapeHtml(interest)}">${escapeHtml(interest)}</button>`).join('')}</div>
                <div class="local-offer"><span class="offer-icon">↗</span><span><small>${copy.whatToDo.toUpperCase()}</small><strong>${escapeHtml(person.experience)}</strong></span></div>
                <div class="profile-footer"><button class="view-profile-button" type="button" data-profile="${escapeHtml(person.id)}">${copy.viewProfile}<span>↗</span></button><span class="profile-language">${person.languages.map(escapeHtml).join(' · ')}</span></div>
            </div>
        </article>`;

    const renderCandidates = () => {
        const available = candidates();
        const featuredPerson = available[0];

        document.querySelector('#local-count').textContent = `(${available.length})`;
        featured.innerHTML = featuredPerson
            ? profileMarkup(featuredPerson, true)
            : `<div class="deck-empty"><span>✳</span><h3>${copy.emptyTitle}</h3><p>${copy.emptyCopy}</p><button type="button" data-reset-filters>${copy.reset} ↗</button></div>`;

        document.querySelector('[data-connect-local]').disabled = !featuredPerson;
        document.querySelector('[data-pass-local]').disabled = !featuredPerson;

        const morePeople = available.slice(1, 5);
        peopleStrip.innerHTML = morePeople.length
            ? morePeople.map((person) => `
                <button class="person-peek" type="button" data-profile="${escapeHtml(person.id)}">
                    <span class="peek-photo"><img src="${escapeHtml(person.photo)}" alt="" loading="lazy"><i class="${person.online ? 'is-online' : ''}"></i></span>
                    <span class="peek-name">${escapeHtml(person.name.split(' ')[0])}, ${person.age}</span>
                    <span class="peek-city">⌖ ${escapeHtml(person.city)}</span>
                </button>`).join('')
            : `<p class="no-more-people">That’s your whole shortlist for now. Pick another interest to keep wandering.</p>`;
    };

    const renderConnections = () => {
        const connectionList = document.querySelector('#connection-list');
        const connections = state.connections.map((id) => localById.get(id)).filter(Boolean);

        document.querySelector('#connection-count').textContent = String(connections.length);
        const unreadCount =
            state.requests.length + Object.values(state.messages).reduce(
                (total, messages) => total + messages.filter((message) => message.unread).length,
                0,
            );
        document.querySelector('#nav-unread').textContent = String(unreadCount);
        document.querySelector('#mobile-nav-unread').textContent = unreadCount ? ' ' : '';

        connectionList.innerHTML = connections.length
            ? connections.map((person) => {
                const messages = messageList(person.id);
                const latest = messages[messages.length - 1];
                return `<button class="connection-person" type="button" data-chat-person="${escapeHtml(person.id)}">
                    <span class="connection-avatar"><img src="${escapeHtml(person.photo)}" alt="" loading="lazy"><i class="${person.online ? 'is-online' : ''}"></i></span>
                    <span class="connection-detail"><strong>${escapeHtml(person.name.split(' ')[0])}</strong><small>${escapeHtml(latest?.text || copy.chatEmpty)}</small></span>
                    ${latest?.unread ? '<i class="connection-unread"></i>' : '<span class="connection-arrow">↗</span>'}
                </button>`;
            }).join('')
            : `<div class="connections-empty"><span>♡</span><p>${copy.noChats}</p></div>`;

        const request = state.requests.map((id) => localById.get(id)).find(Boolean);
        const requestSpot = document.querySelector('#incoming-request');
        requestSpot.innerHTML = request ? `
            <div class="request-card">
                <img src="${escapeHtml(request.photo)}" alt="" loading="lazy">
                <div class="request-copy"><span>${copy.incoming}</span><strong>${escapeHtml(request.name.split(' ')[0])}, ${request.age} <small>· ${escapeHtml(request.city)}</small></strong></div>
                <button type="button" data-accept-request="${escapeHtml(request.id)}" aria-label="${copy.accept}" title="${copy.accept}">♡</button>
                <button type="button" class="request-later" data-dismiss-request="${escapeHtml(request.id)}" aria-label="${copy.later}" title="${copy.later}">×</button>
            </div>` : '';
    };

    const setActiveView = (view) => {
        activeView = view;
        const showingProfile = view === 'profile';
        appMain.hidden = showingProfile;
        profileScreen.hidden = !showingProfile;
        document.querySelector('.social-footer').hidden = showingProfile;
        document.querySelectorAll('[data-view]').forEach((item) => {
                item.classList.toggle('is-active', item.dataset.view === view);
                item.setAttribute('aria-current', item.dataset.view === view ? 'page' : 'false');
        });
        document.querySelectorAll('[data-scroll-to]').forEach((item) => {
                item.classList.toggle('is-active', view === 'map');
                item.setAttribute('aria-current', view === 'map' ? 'location' : 'false');
        });

        if (showingProfile) {
                renderUserProfile();
                window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const renderUserProfile = () => {
        const profile = state.profile;
        const connections = state.connections.map((id) => localById.get(id)).filter(Boolean);

        profileContent.innerHTML = `
                <section class="user-profile-hero">
                    <div class="user-profile-cover"><span>🇹🇳 &nbsp; OUT EXPLORING TUNISIA</span></div>
                    <div class="user-profile-summary">
                        <img class="user-profile-avatar" src="${escapeHtml(profile.photo)}" alt="${escapeHtml(profile.name)}">
                        <div class="user-profile-identity"><p class="section-kicker">TRAVELLER · AHLA MEMBER</p><h1>${escapeHtml(profile.name)}</h1><p>⌖ ${escapeHtml(profile.location)}</p></div>
                        <button class="user-profile-edit" type="button" data-edit-profile>Edit profile <span>↗</span></button>
                    </div>
                    <p class="user-profile-bio">${escapeHtml(profile.bio)}</p>
                    <div class="user-profile-interests">${profile.interests.map((interest) => `<span>${escapeHtml(interest)}</span>`).join('')}</div>
                </section>
                <div class="user-profile-grid">
                    <section class="user-profile-panel">
                        <div class="user-profile-panel-heading"><div><p class="section-kicker">THE PEOPLE YOU’VE MET</p><h2>Your connections</h2></div><span class="user-profile-count">${connections.length}</span></div>
                        <div class="user-profile-connections">${connections.length
                    ? connections.map((person) => `<article class="user-connection-card"><img src="${escapeHtml(person.photo)}" alt="${escapeHtml(person.name)}"><div><strong>${escapeHtml(person.name)}</strong><span>⌖ ${escapeHtml(person.city)}</span></div><button type="button" data-chat-person="${escapeHtml(person.id)}" aria-label="Chat with ${escapeHtml(person.name)}">Message ↗</button></article>`).join('')
                    : '<p class="user-profile-empty">Your next hello could become a new friendship.</p>'}</div>
                    </section>
                    <section class="user-profile-panel">
                        <div class="user-profile-panel-heading"><div><p class="section-kicker">MADE FOR THIS TRIP</p><h2>Plans & experiences</h2></div><span aria-hidden="true">✳</span></div>
                        <div class="user-profile-activities">${connections.length
                    ? connections.map((person) => `<article class="user-activity-card"><span class="activity-symbol">↗</span><div><strong>${escapeHtml(person.experience)}</strong><span>A local plan with ${escapeHtml(person.name.split(' ')[0])} · ${escapeHtml(person.city)}</span></div></article>`).join('')
                    : '<p class="user-profile-empty">Connect with a local to collect your first plan.</p>'}</div>
                    </section>
                </div>`;
    };

    const renderMap = () => {
        const markers = document.querySelector('#map-markers');
        const placeCards = document.querySelector('#city-pills');

        markers.innerHTML = demo.places.map((place) => {
                const x = (place.longitude - 7.25) * 80;
                const y = (37.4 - place.latitude) * 92;
                const labelOnLeft = ['Sidi Bou Said', 'Kairouan'].includes(place.city);
                const labelX = labelOnLeft ? -13 : 13;
                const labelAnchor = labelOnLeft ? 'end' : 'start';
                return `<g class="map-marker${activeCity === place.city ? ' is-selected' : ''}" role="button" tabindex="0" data-map-city="${escapeHtml(place.city)}" aria-label="${escapeHtml(place.city)}: ${place.locals} ${copy.locals}, ${place.experiences} ${copy.experiences}" transform="translate(${x.toFixed(1)} ${y.toFixed(1)})">
                    <circle class="marker-halo" r="22"></circle>
                    <circle class="marker-dot" r="7"></circle>
                    <circle class="marker-core" r="2.5"></circle>
                    <text class="marker-label" text-anchor="${labelAnchor}" x="${labelX}" y="4">${escapeHtml(place.city.toUpperCase())}</text>
                    <text class="marker-count" text-anchor="${labelAnchor}" x="${labelX}" y="17">${place.locals} ${copy.locals}</text>
                </g>`;
        }).join('');

        placeCards.innerHTML = demo.places.map((place) => `
            <button type="button" class="city-pill${activeCity === place.city ? ' is-selected' : ''}" data-map-city="${escapeHtml(place.city)}">
                <span class="city-pill-dot"></span><strong>${escapeHtml(place.city)}</strong><small>${place.locals} ${copy.locals}</small>
            </button>`).join('');

        updateMapCard();
    };

    const updateMapCard = () => {
        const selected = demo.places.find((place) => place.city === activeCity);
        const card = document.querySelector('#map-selection-card');

        card.innerHTML = selected
            ? `<span class="map-selected-kicker">${copy.citySelected}</span><h3>${escapeHtml(selected.city)} <span aria-hidden="true">✳</span></h3><p>${escapeHtml(selected.note)}</p><div class="map-card-stats"><strong>${selected.locals}<small> ${copy.locals}</small></strong><strong>${selected.experiences}<small> ${copy.experiences}</small></strong></div><button type="button" data-see-city="${escapeHtml(selected.city)}">${copy.seeLocals}<span>↗</span></button>`
            : `<span class="map-selected-kicker">${copy.mapPromptTitle}</span><h3>${copy.mapPrompt}</h3><p>From the medina to the oasis, there’s someone who’ll show you around.</p>`;
    };

    const getConversation = (id) => {
        let messages = messageList(id);

        if (!messages.length) {
            messages = [{ from: 'them', text: `Ahla! So happy we connected. What brings you to Tunisia? ☀️`, time: copy.newMessage }];
            state.messages[id] = messages;
        }

        return messages;
    };

    const renderChat = () => {
        const person = localById.get(activeChat);
        const conversationPeople = state.connections.map((id) => localById.get(id)).filter(Boolean);

        chatApp.innerHTML = `
            <aside class="chat-sidebar">
                <div class="chat-sidebar-head"><div><p class="section-kicker">${copy.chats}</p><h2 id="chat-title">${copy.chatTitle}</h2></div><button type="button" class="dialog-close" data-close-chat aria-label="${copy.close}">×</button></div>
                <label class="chat-search"><span aria-hidden="true">⌕</span><input type="search" placeholder="${copy.searchChats}" aria-label="${copy.searchChats}"></label>
                <div class="chat-tabs"><span>${copy.chats}</span><span>${copy.connectionLabel} · ${conversationPeople.length}</span></div>
                <div class="chat-people">${conversationPeople.map((friend) => {
                    const messages = messageList(friend.id);
                    const last = messages[messages.length - 1];
                    return `<button type="button" class="chat-person${friend.id === activeChat ? ' is-selected' : ''}" data-select-chat="${escapeHtml(friend.id)}">
                        <span class="chat-person-photo"><img src="${escapeHtml(friend.photo)}" alt="" loading="lazy"><i class="${friend.online ? 'is-online' : ''}"></i></span>
                        <span class="chat-person-meta"><strong>${escapeHtml(friend.name.split(' ')[0])}</strong><small>${escapeHtml(last?.text || copy.chatEmpty)}</small></span>
                        <span class="chat-time">${escapeHtml(last?.time || '')}</span>
                    </button>`;
                }).join('') || `<div class="chat-empty-sidebar"><span>✳</span><p>${copy.noChats}</p></div>`}</div>
                <div class="chat-demo-label"><span>✦</span> ${copy.demoNote}</div>
            </aside>
            <section class="chat-main">
                ${person ? `
                    <header class="chat-conversation-head">
                        <button class="chat-back-button" type="button" data-back-chats aria-label="Back to conversations">← Chats</button>
                        <img src="${escapeHtml(person.photo)}" alt="" loading="lazy">
                        <div><h3>${escapeHtml(person.name)}</h3><span><i class="${person.online ? 'is-online' : ''}"></i>${person.online ? copy.online : person.city}</span></div>
                        <button type="button" class="chat-profile-link" data-profile="${escapeHtml(person.id)}">${copy.seeProfile} ↗</button>
                        <button type="button" class="chat-mobile-close" data-close-chat aria-label="${copy.close}">×</button>
                    </header>
                    <div class="chat-context-strip"><span>✳</span> You connected over ${escapeHtml(person.interests.slice(0, 2).join(' & '))} · ${escapeHtml(person.city)}, Tunisia</div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="chat-date-label"><span></span>${copy.chatToday}<span></span></div>
                        <div class="conversation-intro"><img src="${escapeHtml(person.photo)}" alt="" loading="lazy"><strong>You and ${escapeHtml(person.name.split(' ')[0])} are connected!</strong><span>Now go make it a good story.</span></div>
                        ${getConversation(person.id).map((message) => `<div class="message-row${message.from === 'me' ? ' is-mine' : ''}"><span class="message-avatar">${message.from === 'me' ? 'S' : `<img src="${escapeHtml(person.photo)}" alt="">`}</span><div class="message-body"><span class="message-sender">${message.from === 'me' ? copy.messageMe : escapeHtml(person.name.split(' ')[0])}</span><p>${escapeHtml(message.text)}</p><time>${escapeHtml(message.time || copy.newMessage)}</time></div></div>`).join('')}
                    </div>
                    <form class="chat-composer" data-message-form>
                        <button type="button" aria-label="Add an emoji" class="composer-emoji">☺</button>
                        <input type="text" name="message" maxlength="500" placeholder="${copy.messagePlaceholder}" autocomplete="off" required>
                        <button type="submit" class="composer-send">${copy.messageSend} <span>↗</span></button>
                    </form>`
                    : `<div class="chat-no-selection"><div class="empty-chat-sun">✳</div><h2>${copy.chatIntro}</h2><p>${copy.startChat}</p><button type="button" data-close-chat>${copy.back}</button></div>`}
            </section>`;

        if (person) {
            const messages = chatApp.querySelector('#chat-messages');
            messages.scrollTop = messages.scrollHeight;
        }
    };

    const openChat = (id = '') => {
        activeChat = id || state.connections[0] || '';
        chatDialog.classList.toggle('is-showing-list', !id);
        renderChat();

        if (!chatDialog.open) {
            chatDialog.showModal();
        }
    };

    const openProfile = (id) => {
        const person = localById.get(id);
        if (!person) return;

        const isConnected = state.connections.includes(id);
        profileDialog.innerHTML = `
            <button class="profile-dialog-close" type="button" data-close-profile aria-label="${copy.close}">×</button>
            <div class="profile-dialog-photo"><img src="${escapeHtml(person.photo)}" alt="${escapeHtml(person.name)}"><span>🇹🇳 &nbsp; ${escapeHtml(person.city)}, Tunisia</span></div>
            <div class="profile-dialog-body"><span class="profile-dialog-kicker">${copy.profile} <i></i> ${person.online ? copy.online : copy.offline}</span>
                <h2 id="profile-name">${escapeHtml(person.name)}, ${person.age}</h2><p class="profile-dialog-role">${escapeHtml(person.role)} · ${escapeHtml(person.distance)}</p>
                <p class="profile-dialog-bio">${escapeHtml(person.bio)}</p>
                <h3>${copy.interests}</h3><div class="profile-dialog-tags">${person.interests.map((interest) => `<span>${escapeHtml(interest)}</span>`).join('')}</div>
                <h3>${copy.languages}</h3><p class="profile-dialog-languages">${person.languages.map(escapeHtml).join(' · ')}</p>
                <div class="profile-dialog-plan"><span>✳</span><div><small>${copy.whatToDo}</small><strong>${escapeHtml(person.experience)}</strong></div></div>
                <p class="profile-dialog-demo">${copy.demoNote}</p>
                ${isConnected
                    ? `<button class="profile-dialog-connect is-connected" type="button" data-chat-person="${escapeHtml(id)}">You're connected — say hi <span>↗</span></button>`
                    : `<button class="profile-dialog-connect" type="button" data-connect-person="${escapeHtml(id)}">${copy.connect} to ${escapeHtml(person.name.split(' ')[0])} <span>♡</span></button>`}
            </div>`;

        if (!profileDialog.open) {
            profileDialog.showModal();
        }
    };

    const connect = (id, shouldOpenChat = true) => {
        const person = localById.get(id);
        if (!person || state.connections.includes(id)) return;

        state.passed = state.passed.filter((passedId) => passedId !== id);
        state.requests = state.requests.filter((requestId) => requestId !== id);
        state.connections = [...state.connections, id];

        if (!state.messages[id]?.length) {
            state.messages[id] = [{
                from: 'them',
                text: `Ahla! So happy we connected. What brings you to Tunisia? ☀️`,
                time: copy.newMessage,
            }];
        }

        persist();
        renderCandidates();
        renderConnections();
        showToast(`✳ ${copy.connected} ${copy.connectedCopy.replace('{name}', person.name.split(' ')[0])}`);

        if (profileDialog.open) profileDialog.close();
        if (shouldOpenChat) openChat(id);
    };

    const pass = () => {
        const person = candidates()[0];
        if (!person) return;

        state.passed = [...state.passed, person.id];
        persist();
        renderCandidates();
        showToast(copy.passed);
    };

    const acceptRequest = (id) => {
        state.requests = state.requests.filter((requestId) => requestId !== id);
        connect(id);
        showToast(copy.requestAccepted);
    };

    const selectCity = (city) => {
        activeCity = activeCity === city ? '' : city;
        renderMap();
        renderCandidates();
        setActiveView('discover');
        document.querySelector('#discover').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    document.addEventListener('click', (event) => {
        const target = event.target.closest('button, a, [data-map-city]');
        if (!target) return;

        if (target.matches('[data-view]')) {
            event.preventDefault();
            setActiveView(target.dataset.view);
            if (target.dataset.view === 'discover') {
                document.querySelector('#discover').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        if (target.matches('[data-scroll-to]')) {
            event.preventDefault();
            setActiveView('map');
            document.getElementById(target.dataset.scrollTo).scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (target.matches('[data-pass-local]')) pass();
        if (target.matches('[data-connect-local]')) {
            const person = candidates()[0];
            if (person) connect(person.id);
        }
        if (target.matches('[data-profile]')) openProfile(target.dataset.profile);
        if (target.matches('[data-connect-person]')) connect(target.dataset.connectPerson);
        if (target.matches('[data-open-chat]')) openChat();
        if (target.matches('[data-chat-person]')) {
            if (profileDialog.open) profileDialog.close();
            openChat(target.dataset.chatPerson);
        }
        if (target.matches('[data-select-chat]')) {
            activeChat = target.dataset.selectChat;
            chatDialog.classList.remove('is-showing-list');
            state.messages[activeChat] = messageList(activeChat).map((message) => ({ ...message, unread: false }));
            persist();
            renderConnections();
            renderChat();
        }
        if (target.matches('[data-accept-request]')) acceptRequest(target.dataset.acceptRequest);
        if (target.matches('[data-dismiss-request]')) {
            state.requests = state.requests.filter((id) => id !== target.dataset.dismissRequest);
            persist();
            renderConnections();
            showToast(copy.requestDismissed);
        }
        if (target.matches('[data-map-city]')) selectCity(target.dataset.mapCity);
        if (target.matches('[data-see-city]')) {
            activeCity = target.dataset.seeCity;
            renderCandidates();
            setActiveView('discover');
            document.querySelector('#discover').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (target.matches('[data-reset-filters]')) {
            activeInterest = '';
            activeCity = '';
            document.querySelectorAll('[data-interest]').forEach((chip) => {
                chip.classList.toggle('is-selected', chip.dataset.interest === '');
            });
            renderMap();
            renderCandidates();
        }
        if (target.matches('[data-interest]')) {
            activeInterest = target.dataset.interest;
            activeCity = '';
            document.querySelectorAll('[data-interest]').forEach((chip) => {
                chip.classList.toggle('is-selected', chip === target);
            });
            renderMap();
            renderCandidates();
        }
        if (target.matches('[data-select-interest]')) {
            activeInterest = target.dataset.selectInterest;
            activeCity = '';
            document.querySelectorAll('[data-interest]').forEach((chip) => {
                chip.classList.toggle('is-selected', chip.dataset.interest === activeInterest);
            });
            renderMap();
            renderCandidates();
        }
        if (target.matches('[data-close-profile]')) profileDialog.close();
        if (target.matches('[data-edit-profile]')) {
            editProfileForm.elements.name.value = state.profile.name;
            editProfileForm.elements.location.value = state.profile.location;
            editProfileForm.elements.bio.value = state.profile.bio;
            editProfileForm.elements.interests.value = state.profile.interests.join(', ');
            editProfileDialog.showModal();
        }
        if (target.matches('[data-close-edit-profile]')) editProfileDialog.close();
        if (target.matches('[data-close-chat]')) chatDialog.close();
        if (target.matches('[data-back-chats]')) chatDialog.classList.add('is-showing-list');
    });

    document.querySelector('#map-markers').addEventListener('keydown', (event) => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-map-city]')) {
            event.preventDefault();
            selectCity(event.target.dataset.mapCity);
        }
    });

    editProfileForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = new FormData(editProfileForm);
        state.profile = {
            ...state.profile,
            name: String(formData.get('name')).trim(),
            location: String(formData.get('location')).trim(),
            bio: String(formData.get('bio')).trim(),
            interests: String(formData.get('interests')).split(',').map((interest) => interest.trim()).filter(Boolean).slice(0, 8),
        };
        persist();
        renderUserProfile();
        editProfileDialog.close();
        showToast('Your profile is up to date.');
    });

    chatApp.addEventListener('submit', (event) => {
        if (!event.target.matches('[data-message-form]')) return;
        event.preventDefault();

        const input = event.target.elements.message;
        const text = input.value.trim();
        if (!text || !activeChat || !state.connections.includes(activeChat)) return;

        state.messages[activeChat] = [
            ...messageList(activeChat),
            { from: 'me', text, time: copy.newMessage },
        ];
        persist();
        renderConnections();
        renderChat();
    });

    chatApp.addEventListener('input', (event) => {
        if (!event.target.matches('.chat-search input')) return;
        const query = event.target.value.trim().toLocaleLowerCase();
        chatApp.querySelectorAll('.chat-person').forEach((person) => {
            person.hidden = !person.textContent.toLocaleLowerCase().includes(query);
        });
    });

    [profileDialog, chatDialog].forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    renderCandidates();
    renderConnections();
    renderMap();
    renderUserProfile();
}
