import { test, expect } from '@playwright/test';
import { randomUUID } from 'crypto';
import {
    clearRedisAnalyticsKeys,
    clearMongoAnalytics,
    getRedisHash,
    getMovieUuids,
    closeAll,
} from '../helpers/analytics-db';
import { buildValidPayload, postAnalyticsEvent } from '../helpers/api';

let movieA: string;

test.beforeAll(async () => {
    const uuids = await getMovieUuids(1);
    if (uuids.length < 1) {
        throw new Error('Need at least 1 movie with UUID in the database');
    }
    [movieA] = uuids;
});

test.afterAll(async () => {
    await closeAll();
});

// ── B1. Invalid domain rejected ────────────────────────────────────────

test('B1: invalid domain is rejected with 422', async ({ request }) => {
    const payload = buildValidPayload({ domain: 'evil', entity_id: movieA });
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('domain');
});

// ── B2. Invalid entity_type rejected ───────────────────────────────────

test('B2: invalid entity_type is rejected with 422', async ({ request }) => {
    const payload = buildValidPayload({ entity_type: 'user', entity_id: movieA });
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('entity_type');
});

// ── B3. Invalid action rejected ────────────────────────────────────────

test('B3: invalid action is rejected with 422', async ({ request }) => {
    const payload = buildValidPayload({ action: 'hack', entity_id: movieA });
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('action');
});

// ── B4. Missing required fields rejected ───────────────────────────────

test('B4a: missing event_id is rejected with 422', async ({ request }) => {
    const payload = buildValidPayload({ entity_id: movieA });
    delete (payload as any).event_id;
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('event_id');
});

test('B4b: missing occurred_at is rejected with 422', async ({ request }) => {
    const payload = buildValidPayload({ entity_id: movieA });
    delete (payload as any).occurred_at;
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('occurred_at');
});

// ── B5. Value out of range rejected ────────────────────────────────────

test('B5a: value=0 is rejected (min: 1)', async ({ request }) => {
    const payload = buildValidPayload({ value: 0, entity_id: movieA });
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('value');
});

test('B5b: value=101 is rejected (max: 100)', async ({ request }) => {
    const payload = buildValidPayload({ value: 101, entity_id: movieA });
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('value');
});

test('B5c: value="one" is rejected (must be integer)', async ({ request }) => {
    const payload = { ...buildValidPayload({ entity_id: movieA }), value: 'one' };
    const res = await postAnalyticsEvent(request, payload);

    expect(res.status()).toBe(422);
    const body = await res.json();
    expect(body.errors).toHaveProperty('value');
});

// ── C1. Duplicate event_id counted once ────────────────────────────────

test('C1: same event_id sent twice only increments counter once', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const uniqueEntity = randomUUID(); // Use unique entity to avoid stale B7 data
    const eventId = randomUUID();
    const payload = buildValidPayload({
        event_id: eventId,
        entity_id: uniqueEntity,
        action: 'view',
    });

    // First POST
    const res1 = await postAnalyticsEvent(request, payload);
    expect(res1.status()).toBe(202);

    // Second POST with same event_id
    const res2 = await postAnalyticsEvent(request, payload);
    expect(res2.status()).toBe(202);

    // Check Redis counter — should be 1, not 2
    const counterKey = `anl:counters:jav:movie:${uniqueEntity}`;
    const hash = await getRedisHash(counterKey);
    expect(parseInt(hash['view'] || '0', 10)).toBe(1);
});

// ── X2. Multiple entity_types generate separate Redis keys ─────────────

test('X2: different entity_types create separate Redis counter keys', async ({ request }) => {
    await clearRedisAnalyticsKeys();

    const entityId = randomUUID();
    const entityTypes = ['movie', 'actor', 'tag'];

    for (const entityType of entityTypes) {
        const payload = buildValidPayload({
            entity_type: entityType,
            entity_id: entityId,
            event_id: randomUUID(),
        });
        const res = await postAnalyticsEvent(request, payload);
        expect(res.status()).toBe(202);
    }

    // Assert: three separate Redis hash keys exist
    for (const entityType of entityTypes) {
        const key = `anl:counters:jav:${entityType}:${entityId}`;
        const hash = await getRedisHash(key);
        expect(parseInt(hash['view'] || '0', 10)).toBe(1);
    }
});

// ── X5. Value field defaults to 1 when not provided ────────────────────

test('X5: omitting value field defaults to value=1', async ({ request }) => {
    await clearRedisAnalyticsKeys();

    const entityId = randomUUID(); // Use unique entity to avoid stale data
    const payload = buildValidPayload({ entity_id: entityId });
    delete (payload as any).value;

    const res = await postAnalyticsEvent(request, payload);
    expect(res.status()).toBe(202);

    // Redis counter should have incremented by 1
    const counterKey = `anl:counters:jav:movie:${entityId}`;
    const hash = await getRedisHash(counterKey);
    expect(parseInt(hash['view'] || '0', 10)).toBe(1);
});

// ── B7. Rate limit enforcement ─────────────────────────────────────────
// MUST be last test in this file — it exhausts the 60/min rate limit

test('B7: burst requests trigger 429 rate limit', async ({ request }) => {
    // Send 65+ requests rapidly to exceed 60/min rate limit
    const results: number[] = [];
    const BURST_COUNT = 70;

    const promises = Array.from({ length: BURST_COUNT }, async () => {
        const payload = buildValidPayload({
            event_id: randomUUID(),
            entity_id: movieA,
        });
        const res = await postAnalyticsEvent(request, payload);
        results.push(res.status());
    });

    await Promise.all(promises);

    // At least one response should be 429
    const rateLimited = results.filter((s) => s === 429);
    expect(rateLimited.length).toBeGreaterThanOrEqual(1);
});
