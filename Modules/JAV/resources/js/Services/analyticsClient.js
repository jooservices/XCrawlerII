import axios from 'axios';

const SESSION_PREFIX = 'anl:view:v1:';
const inMemory = new Set();

function makeViewKey(movieUuid) {
    return `${SESSION_PREFIX}${movieUuid}`;
}

function alreadyTracked(movieUuid) {
    const key = makeViewKey(movieUuid);

    if (inMemory.has(key)) {
        return true;
    }

    try {
        if (window.sessionStorage.getItem(key) === '1') {
            inMemory.add(key);
            return true;
        }
    } catch {
        // ignore sessionStorage issues
    }

    return false;
}

function markTracked(movieUuid) {
    const key = makeViewKey(movieUuid);
    inMemory.add(key);

    try {
        window.sessionStorage.setItem(key, '1');
    } catch {
        // ignore sessionStorage issues
    }
}

export async function trackMovieView(movieUuid) {
    if (!movieUuid || alreadyTracked(movieUuid)) {
        return;
    }

    const payload = {
        event_id: crypto.randomUUID(),
        domain: 'jav',
        entity_type: 'movie',
        entity_id: movieUuid,
        action: 'view',
        value: 1,
        occurred_at: new Date().toISOString(),
    };

    await axios.post('/api/v1/analytics/events', payload);
    markTracked(movieUuid);
}
