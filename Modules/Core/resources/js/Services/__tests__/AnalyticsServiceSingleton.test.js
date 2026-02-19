import test from 'node:test';
import assert from 'node:assert/strict';
import analyticsService from '../analyticsService.js';

const originalHttpClient = analyticsService.httpClient;
const originalStorage = analyticsService.storage;
const originalUuidFactory = analyticsService.uuidFactory;
const originalNowFactory = analyticsService.nowFactory;

function resetSingleton() {
    analyticsService.httpClient = originalHttpClient;
    analyticsService.storage = originalStorage;
    analyticsService.uuidFactory = originalUuidFactory;
    analyticsService.nowFactory = originalNowFactory;
    analyticsService.inMemory.clear();
}

test('analyticsService singleton sends API request on first track', async (t) => {
    const calls = [];
    analyticsService.httpClient = {
        async post(url, payload) {
            calls.push({ url, payload });
        },
    };
    analyticsService.storage = null;
    analyticsService.uuidFactory = () => 'evt-singleton-1';
    analyticsService.nowFactory = () => new Date('2026-02-19T10:00:00.000Z');
    analyticsService.inMemory.clear();

    t.after(() => resetSingleton());

    const sent = await analyticsService.track('view', 'movie', 'movie-uuid-singleton');

    assert.equal(sent, true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/api/v1/analytics/events');
    assert.equal(calls[0].payload.event_id, 'evt-singleton-1');
    assert.equal(calls[0].payload.action, 'view');
});

test('analyticsService singleton dedupes repeated track calls', async (t) => {
    const calls = [];
    analyticsService.httpClient = {
        async post(url, payload) {
            calls.push({ url, payload });
        },
    };
    analyticsService.storage = null;
    analyticsService.uuidFactory = () => 'evt-singleton-2';
    analyticsService.nowFactory = () => new Date('2026-02-19T10:00:00.000Z');
    analyticsService.inMemory.clear();

    t.after(() => resetSingleton());

    const first = await analyticsService.track('view', 'movie', 'movie-uuid-singleton');
    const second = await analyticsService.track('view', 'movie', 'movie-uuid-singleton');

    assert.equal(first, true);
    assert.equal(second, false);
    assert.equal(calls.length, 1);
});

test('analyticsService singleton returns false on API failure', async (t) => {
    let attempts = 0;
    analyticsService.httpClient = {
        async post() {
            attempts += 1;
            throw new Error('network failure');
        },
    };
    analyticsService.storage = null;
    analyticsService.uuidFactory = () => 'evt-singleton-3';
    analyticsService.nowFactory = () => new Date('2026-02-19T10:00:00.000Z');
    analyticsService.inMemory.clear();

    t.after(() => resetSingleton());

    const sent = await analyticsService.track('view', 'movie', 'movie-uuid-singleton');

    assert.equal(sent, false);
    assert.equal(attempts, 1);
});
