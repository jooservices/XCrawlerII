import { test, expect } from '@playwright/test';
import { randomUUID } from 'crypto';
import {
    clearRedisAnalyticsKeys,
    clearMongoAnalytics,
    getMongoTotals,
    getMongoDailyDocs,
    getMongoWeeklyDocs,
    getMongoMonthlyDocs,
    getMongoYearlyDocs,
    getMovieUuids,
    closeAll,
} from '../helpers/analytics-db';
import { buildValidPayload, postAnalyticsEvent } from '../helpers/api';
import { runFlush, resetRateLimiter } from '../helpers/analytics-cli';

let movieC: string;

test.beforeAll(async () => {
    resetRateLimiter();
    const uuids = await getMovieUuids(3);
    // Use the 3rd movie UUID (or 1st if less than 3 available)
    movieC = uuids[2] || uuids[0];
    if (!movieC) {
        throw new Error('Need at least 1 movie with UUID in the database');
    }
});

test.afterAll(async () => {
    await closeAll();
});

// ── C2. Date boundary daily bucket ─────────────────────────────────────

test('C2: events spanning two dates produce two daily docs', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;

    // Event on 2026-02-19
    const payload1 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-02-19T23:59:00Z',
    });
    const res1 = await postAnalyticsEvent(request, payload1);
    expect(res1.status()).toBe(202);

    // Event on 2026-02-20
    const payload2 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-02-20T00:00:01Z',
    });
    const res2 = await postAnalyticsEvent(request, payload2);
    expect(res2.status()).toBe(202);

    // Flush
    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: two daily rows exist
    const dailyDocs = await getMongoDailyDocs(entityId);
    expect(dailyDocs.length).toBe(2);

    const dates = dailyDocs.map((d) => d.date).sort();
    expect(dates).toContain('2026-02-19');
    expect(dates).toContain('2026-02-20');

    // Each has correct increment
    for (const doc of dailyDocs) {
        expect(doc.view).toBe(1);
    }
});

// ── C3. Weekly bucket correctness ──────────────────────────────────────

test('C3: events in same ISO week produce one weekly doc with correct sum', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;

    // 2026-02-16 is Monday (Week 8), 2026-02-18 is Wednesday (same week)
    const payload1 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-02-16T10:00:00Z',
    });
    const payload2 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-02-18T10:00:00Z',
    });

    await postAnalyticsEvent(request, payload1);
    await postAnalyticsEvent(request, payload2);

    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: one weekly row for 2026-W08
    const weeklyDocs = await getMongoWeeklyDocs(entityId);
    expect(weeklyDocs.length).toBe(1);
    expect(weeklyDocs[0].week).toBe('2026-W08');
    expect(weeklyDocs[0].view).toBe(2);
});

// ── C4. Monthly bucket correctness ─────────────────────────────────────

test('C4: events in two different months produce two monthly docs', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;

    // January event
    const payload1 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-01-15T10:00:00Z',
    });
    // February event
    const payload2 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-02-15T10:00:00Z',
    });

    await postAnalyticsEvent(request, payload1);
    await postAnalyticsEvent(request, payload2);

    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: two monthly docs
    const monthlyDocs = await getMongoMonthlyDocs(entityId);
    expect(monthlyDocs.length).toBe(2);

    const months = monthlyDocs.map((d) => d.month).sort();
    expect(months).toContain('2026-01');
    expect(months).toContain('2026-02');

    for (const doc of monthlyDocs) {
        expect(doc.view).toBe(1);
    }
});

// ── C5. Yearly bucket correctness ──────────────────────────────────────

test('C5: events in same year produce one yearly doc', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;

    const payload1 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-03-01T10:00:00Z',
    });
    const payload2 = buildValidPayload({
        entity_id: entityId,
        event_id: randomUUID(),
        occurred_at: '2026-06-15T10:00:00Z',
    });

    await postAnalyticsEvent(request, payload1);
    await postAnalyticsEvent(request, payload2);

    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: one yearly doc for 2026
    const yearlyDocs = await getMongoYearlyDocs(entityId);
    expect(yearlyDocs.length).toBe(1);
    expect(yearlyDocs[0].year).toBe('2026');
    expect(yearlyDocs[0].view).toBe(2);
});

// ── C6. Flush idempotency ──────────────────────────────────────────────

test('C6: running flush twice does not double-count', async ({ request }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;

    // Insert 3 events
    for (let i = 0; i < 3; i++) {
        const payload = buildValidPayload({
            entity_id: entityId,
            event_id: randomUUID(),
            occurred_at: '2026-02-19T10:00:00Z',
        });
        await postAnalyticsEvent(request, payload);
    }

    // First flush
    const flush1 = runFlush();
    expect(flush1.exitCode).toBe(0);
    expect(flush1.keysProcessed).toBeGreaterThanOrEqual(1);

    const totals1 = await getMongoTotals(entityId);
    expect(totals1).not.toBeNull();
    expect(totals1!.view).toBe(3);

    // Second flush — should process 0 keys (already consumed)
    const flush2 = runFlush();
    expect(flush2.exitCode).toBe(0);
    expect(flush2.keysProcessed).toBe(0);

    // Totals unchanged
    const totals2 = await getMongoTotals(entityId);
    expect(totals2!.view).toBe(3);
});

// ── C8. Large volume stress ────────────────────────────────────────────

test('C8: 500 events flush correctly without crash', async ({ request }) => {
    test.slow(); // Triple the default timeout for stress test
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    const entityId = movieC;
    const EVENT_COUNT = 500;

    // Generate events in batches to avoid overwhelming the server
    const BATCH_SIZE = 50;
    for (let batch = 0; batch < Math.ceil(EVENT_COUNT / BATCH_SIZE); batch++) {
        const promises = [];
        const end = Math.min((batch + 1) * BATCH_SIZE, EVENT_COUNT);
        for (let i = batch * BATCH_SIZE; i < end; i++) {
            const payload = buildValidPayload({
                entity_id: entityId,
                event_id: randomUUID(),
                occurred_at: '2026-02-19T10:00:00Z',
            });
            promises.push(postAnalyticsEvent(request, payload));
        }
        await Promise.all(promises);
    }

    // Flush
    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);
    expect(flushResult.errors).toBe(0);

    // Assert: totals match
    const totals = await getMongoTotals(entityId);
    expect(totals).not.toBeNull();
    expect(totals!.view).toBe(EVENT_COUNT);

    // Parity check should pass (if movie exists in MySQL)
    const mysqlCounters = await (await import('../helpers/analytics-db')).getMysqlMovieCounters(entityId);
    if (mysqlCounters) {
        expect(mysqlCounters.views).toBe(EVENT_COUNT);
    }
});
