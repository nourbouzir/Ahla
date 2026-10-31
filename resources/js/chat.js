// Chat polling (no websockets in v1). Everything is built with textContent, never innerHTML,
// so a message can never inject markup. Without JavaScript the page still works with reloads.
const chat = document.getElementById('chat');

if (chat) {
    let last = Number(chat.dataset.last || 0);
    const empty = document.getElementById('chat-empty');

    const render = (m) => {
        const li = document.createElement('li');
        li.dataset.id = m.id;
        li.className = 'max-w-[85%] rounded-xl border p-3 ' + (m.mine
            ? 'ms-auto border-door-200 bg-door-50'
            : 'me-auto border-door-100 bg-white');

        const meta = document.createElement('p');
        meta.className = 'flex items-center gap-2 text-xs text-slate-600';
        let face;
        if (m.avatar) {
            face = document.createElement('img');
            face.src = m.avatar;
            face.alt = '';
            face.width = 24;
            face.height = 24;
            face.className = 'h-6 w-6 rounded-full object-cover';
        } else {
            face = document.createElement('span');
            face.setAttribute('aria-hidden', 'true');
            face.className = 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-door-700 text-[10px] font-semibold text-white';
            face.textContent = m.initials;
        }
        const who = document.createElement('span');
        who.textContent = `${m.sender} - ${m.at}`;
        meta.append(face, who);

        const text = document.createElement('p');
        text.className = 'mt-1 whitespace-pre-wrap break-words';
        text.textContent = m.text;
        li.append(meta, text);

        if (m.status === 'translated') {
            const details = document.createElement('details');
            details.className = 'mt-1 text-sm';
            const summary = document.createElement('summary');
            summary.className = 'cursor-pointer underline';
            summary.textContent = chat.dataset.original;
            const original = document.createElement('p');
            original.className = 'mt-1 whitespace-pre-wrap break-words';
            original.lang = m.language || '';
            original.textContent = m.original;
            details.append(summary, original);
            li.append(details);
        } else if (m.status === 'pending' || m.status === 'unavailable') {
            const note = document.createElement('p');
            note.className = 'mt-1 text-xs text-slate-600';
            note.textContent = chat.dataset[m.status];
            li.append(note);
        }

        return li;
    };

    const poll = async () => {
        try {
            const response = await fetch(`${chat.dataset.poll}?after=${last}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const { messages } = await response.json();
            messages.forEach((m) => {
                chat.append(render(m));
                last = Math.max(last, m.id);
            });
            if (messages.length && empty) empty.classList.add('hidden');
        } catch (e) {
            // Network hiccup: try again at the next tick.
        }
    };

    setInterval(() => { if (!document.hidden) poll(); }, 5000);
}
