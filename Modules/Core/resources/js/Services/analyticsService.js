import axios from 'axios';

const ALLOWED_ACTIONS = new Set(['view', 'download']);
const ALLOWED_ENTITY_TYPES = new Set(['movie', 'actor', 'tag']);
const SESSION_PREFIX = 'anl:track:v1:';

function fallbackRandomId() {
    return `evt-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

export class AnalyticsService {
    constructor({
        httpClient = axios,
        storage = globalThis.window?.sessionStorage ?? null,
        endpoint = '/api/v1/analytics/events',
        defaultDomain = 'jav',
        uuidFactory = () => globalThis.crypto?.randomUUID?.() ?? fallbackRandomId(),
        nowFactory = () => new Date(),
    } = {}) {
        this.httpClient = httpClient;
        this.storage = storage;
        this.endpoint = endpoint;
        this.defaultDomain = defaultDomain;
        this.uuidFactory = uuidFactory;
        this.nowFactory = nowFactory;
        this.inMemory = new Set();
    }

    makeTrackKey(action, entityType, entityId) {
        return `${SESSION_PREFIX}${action}:${entityType}:${entityId}`;
    }

    hasTracked(trackKey) {
        if (this.inMemory.has(trackKey)) {
            return true;
        }

        if (! this.storage) {
            return false;
        }

        try {
            const stored = this.storage.getItem(trackKey) === '1';
            if (stored) {
                this.inMemory.add(trackKey);
            }

            return stored;
        } catch {
            return false;
        }
    }

    markTracked(trackKey) {
        this.inMemory.add(trackKey);

        if (! this.storage) {
            return;
        }

        try {
            this.storage.setItem(trackKey, '1');
        } catch {
            // Ignore storage failures and keep in-memory dedupe.
        }
    }

    /**
     * @param {string} action
     * @param {string} entityType
     * @param {string} entityId
     * @param {{userId?: number | null, value?: number, domain?: string, dedupe?: boolean, eventId?: string, occurredAt?: string}} [options]
     * @returns {Promise<boolean>}
     */
    async track(action, entityType, entityId, options = {}) {
        if (! ALLOWED_ACTIONS.has(action)) {
            return false;
        }

        if (! ALLOWED_ENTITY_TYPES.has(entityType) || ! entityId) {
            return false;
        }

        const domain = options.domain ?? this.defaultDomain;
        if (domain !== this.defaultDomain) {
            return false;
        }

        const dedupe = options.dedupe ?? true;
        const trackKey = this.makeTrackKey(action, entityType, entityId);
        if (dedupe && this.hasTracked(trackKey)) {
            return false;
        }

        const payload = {
            event_id: options.eventId ?? this.uuidFactory(),
            domain,
            entity_type: entityType,
            entity_id: entityId,
            action,
            value: 1,
            occurred_at: options.occurredAt ?? this.nowFactory().toISOString(),
        };

        if (typeof options.userId === 'number') {
            payload.user_id = options.userId;
        }

        try {
            await this.httpClient.post(this.endpoint, payload);
            if (dedupe) {
                this.markTracked(trackKey);
            }

            return true;
        } catch {
            return false;
        }
    }
}

const analyticsService = new AnalyticsService();

export default analyticsService;
