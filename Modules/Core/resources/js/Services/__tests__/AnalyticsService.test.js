import test from 'node:test';
import assert from 'node:assert/strict';
import { AnalyticsService } from '../analyticsService.js';

function makeStorage({ throwOnGet = false, throwOnSet = false } = {}) {
    const db = new Map();

    return {
        _db: db,
        getItem(key) {
            if (throwOnGet) {
                throw new Error('get failure');
            }

            return db.get(key) ?? null;
        },
        setItem(key, value) {
            if (throwOnSet) {
                throw new Error('set failure');
            }

            db.set(key, value);
        },
    };
}

function makeService(overrides = {}) {
    const calls = [];
    const httpClient = {
        async post(url, payload) {
            calls.push({ url, payload });
        },
    };

    const service = new AnalyticsService({
        httpClient,
        storage: makeStorage(),
        uuidFactory: () => 'evt-1',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
        ...overrides,
    });

    return { service, calls };
}

test('AnalyticsService sends valid view event to core endpoint', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'movie', 'movie-uuid-1', { userId: 99 });

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/api/v1/analytics/events');
    assert.deepEqual(calls[0].payload, {
        event_id: 'evt-1',
        domain: 'jav',
        entity_type: 'movie',
        entity_id: 'movie-uuid-1',
        action: 'view',
        value: 1,
        occurred_at: '2026-02-19T10:00:00.000Z',
        user_id: 99,
    });
});

test('AnalyticsService accepts download action for movie entity', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('download', 'movie', 'movie-uuid-1');

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].payload.action, 'download');
});

test('AnalyticsService accepts actor and tag entity types', async () => {
    const { service, calls } = makeService();

    const actorSent = await service.track('view', 'actor', 'actor-uuid-1');
    const tagSent = await service.track('view', 'tag', 'tag-uuid-1');

    assert.equal(actorSent, true);
    assert.equal(tagSent, true);
    assert.equal(calls.length, 2);
});

test('AnalyticsService deduplicates same event key in same session', async () => {
    const { service, calls } = makeService();

    const first = await service.track('view', 'movie', 'movie-uuid-1');
    const second = await service.track('view', 'movie', 'movie-uuid-1');

    assert.equal(first, true);
    assert.equal(second, false);
    assert.equal(calls.length, 1);
});

test('AnalyticsService dedupe key is action-aware', async () => {
    const { service, calls } = makeService();

    const viewSent = await service.track('view', 'movie', 'movie-uuid-1');
    const downloadSent = await service.track('download', 'movie', 'movie-uuid-1');

    assert.equal(viewSent, true);
    assert.equal(downloadSent, true);
    assert.equal(calls.length, 2);
});

test('AnalyticsService dedupe key is entity-aware', async () => {
    const { service, calls } = makeService();

    const first = await service.track('view', 'movie', 'movie-uuid-1');
    const second = await service.track('view', 'movie', 'movie-uuid-2');

    assert.equal(first, true);
    assert.equal(second, true);
    assert.equal(calls.length, 2);
});

test('AnalyticsService deduplicates using session storage between instances', async () => {
    const storage = makeStorage();
    const calls = [];
    const httpClient = {
        async post(url, payload) {
            calls.push({ url, payload });
        },
    };

    const service1 = new AnalyticsService({
        httpClient,
        storage,
        uuidFactory: () => 'evt-a',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });
    const service2 = new AnalyticsService({
        httpClient,
        storage,
        uuidFactory: () => 'evt-b',
        nowFactory: () => new Date('2026-02-19T10:00:01.000Z'),
    });

    const first = await service1.track('view', 'movie', 'movie-uuid-2');
    const second = await service2.track('view', 'movie', 'movie-uuid-2');

    assert.equal(first, true);
    assert.equal(second, false);
    assert.equal(calls.length, 1);
});

test('AnalyticsService reads existing tracked state from storage and skips post', async () => {
    const storage = makeStorage();
    const seededKey = 'anl:track:v1:view:movie:movie-uuid-9';
    storage._db.set(seededKey, '1');
    const calls = [];
    const service = new AnalyticsService({
        storage,
        httpClient: {
            async post(url, payload) {
                calls.push({ url, payload });
            },
        },
        uuidFactory: () => 'evt-1',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });

    const sent = await service.track('view', 'movie', 'movie-uuid-9');

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects invalid action (safety)', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('favorite', 'movie', 'movie-uuid-1');

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects malformed action casing (safety)', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('VIEW', 'movie', 'movie-uuid-1');

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects invalid entity type (safety)', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'provider', 'provider-1');

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects empty entity id (edge)', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'movie', '');

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects null or undefined entity id (edge)', async () => {
    const { service, calls } = makeService();

    const nullSent = await service.track('view', 'movie', null);
    const undefinedSent = await service.track('view', 'movie', undefined);

    assert.equal(nullSent, false);
    assert.equal(undefinedSent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService rejects foreign domain override (exploit guard)', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'movie', 'movie-uuid-1', { domain: 'other' });

    assert.equal(sent, false);
    assert.equal(calls.length, 0);
});

test('AnalyticsService allows explicit default domain override', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'movie', 'movie-uuid-1', { domain: 'jav' });

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
});

test('AnalyticsService supports custom endpoint configuration', async () => {
    const calls = [];
    const service = new AnalyticsService({
        storage: makeStorage(),
        endpoint: '/custom/analytics/events',
        httpClient: {
            async post(url, payload) {
                calls.push({ url, payload });
            },
        },
        uuidFactory: () => 'evt-1',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });

    const sent = await service.track('view', 'movie', 'movie-uuid-1');

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/custom/analytics/events');
});

test('AnalyticsService supports explicit event_id and occurred_at overrides', async () => {
    const { service, calls } = makeService();

    const sent = await service.track('view', 'movie', 'movie-uuid-1', {
        eventId: 'evt-custom',
        occurredAt: '2026-02-20T00:00:00Z',
    });

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].payload.event_id, 'evt-custom');
    assert.equal(calls[0].payload.occurred_at, '2026-02-20T00:00:00Z');
});

test('AnalyticsService includes user_id only when it is a number', async () => {
    const { service, calls } = makeService();

    const first = await service.track('view', 'movie', 'movie-uuid-1', { userId: 'abc' });
    const second = await service.track('view', 'movie', 'movie-uuid-2', { userId: null });
    const third = await service.track('view', 'movie', 'movie-uuid-3', { userId: 7 });

    assert.equal(first, true);
    assert.equal(second, true);
    assert.equal(third, true);
    assert.equal(calls.length, 3);
    assert.equal('user_id' in calls[0].payload, false);
    assert.equal('user_id' in calls[1].payload, false);
    assert.equal(calls[2].payload.user_id, 7);
});

test('AnalyticsService can disable dedupe explicitly', async () => {
    const { service, calls } = makeService();

    const first = await service.track('view', 'movie', 'movie-uuid-1', { dedupe: false });
    const second = await service.track('view', 'movie', 'movie-uuid-1', { dedupe: false });

    assert.equal(first, true);
    assert.equal(second, true);
    assert.equal(calls.length, 2);
});

test('AnalyticsService dedupes without storage using in-memory fallback', async () => {
    const calls = [];
    const service = new AnalyticsService({
        storage: null,
        httpClient: {
            async post(url, payload) {
                calls.push({ url, payload });
            },
        },
        uuidFactory: () => 'evt-1',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });

    const first = await service.track('view', 'movie', 'movie-uuid-1');
    const second = await service.track('view', 'movie', 'movie-uuid-1');

    assert.equal(first, true);
    assert.equal(second, false);
    assert.equal(calls.length, 1);
});

test('AnalyticsService does not throw when HTTP fails and does not mark tracked', async () => {
    let attempt = 0;
    const httpClient = {
        async post() {
            attempt += 1;
            throw new Error('network');
        },
    };

    const service = new AnalyticsService({
        httpClient,
        storage: makeStorage(),
        uuidFactory: () => `evt-${attempt}`,
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });

    const first = await service.track('view', 'movie', 'movie-uuid-1');
    const second = await service.track('view', 'movie', 'movie-uuid-1');

    assert.equal(first, false);
    assert.equal(second, false);
    assert.equal(attempt, 2);
});

test('AnalyticsService tolerates storage failures', async () => {
    const calls = [];
    const service = new AnalyticsService({
        httpClient: {
            async post(url, payload) {
                calls.push({ url, payload });
            },
        },
        storage: makeStorage({ throwOnGet: true, throwOnSet: true }),
        uuidFactory: () => 'evt-1',
        nowFactory: () => new Date('2026-02-19T10:00:00.000Z'),
    });

    const first = await service.track('view', 'movie', 'movie-uuid-1');
    const second = await service.track('view', 'movie', 'movie-uuid-1');

    assert.equal(first, true);
    assert.equal(second, false);
    assert.equal(calls.length, 1);
});
