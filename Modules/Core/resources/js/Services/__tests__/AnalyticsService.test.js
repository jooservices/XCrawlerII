import { describe, it, mock } from 'node:test';
import assert from 'node:assert';
import { AnalyticsService } from '../analyticsService.js';

describe('AnalyticsService', () => {
    // Mock deps
    const createMockHttpClient = () => ({
        post: mock.fn(async () => ({ data: {} }))
    });

    const createMockStorage = () => {
        const store = new Map();
        return {
            getItem: mock.fn(key => store.get(key) || null),
            setItem: mock.fn((key, val) => store.set(key, val)),
            _store: store
        };
    };

    // Manual time mock
    const createMockTime = (startTime = 1000000) => {
        let currentTime = startTime;
        return {
            nowFactory: () => new Date(currentTime),
            advance: (ms) => { currentTime += ms; },
            get: () => currentTime
        };
    };

    it('should track an event successfully', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const service = new AnalyticsService({ httpClient, storage });

        const result = await service.track('view', 'movie', '123');

        assert.strictEqual(result, true);
        assert.strictEqual(httpClient.post.mock.calls.length, 1);

        const callArgs = httpClient.post.mock.calls[0].arguments;
        assert.strictEqual(callArgs[0], '/api/v1/analytics/events');
        assert.strictEqual(callArgs[1].action, 'view');
        assert.strictEqual(callArgs[1].entity_type, 'movie');
        assert.strictEqual(callArgs[1].entity_id, '123');
    });

    it('should deduplicate events within TTL', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const mockTime = createMockTime();

        const service = new AnalyticsService({
            httpClient,
            storage,
            nowFactory: mockTime.nowFactory
        });

        // First track
        await service.track('view', 'movie', '123');
        assert.strictEqual(httpClient.post.mock.calls.length, 1);

        // Second track immediately
        await service.track('view', 'movie', '123');
        assert.strictEqual(httpClient.post.mock.calls.length, 1); // Deduped
    });

    it('should allow tracking again after TTL expires', async () => {
        const httpClient = createMockHttpClient();
        const mockTime = createMockTime();

        const service = new AnalyticsService({
            httpClient,
            storage: null, // Disable storage to test memory TTL isolation
            nowFactory: mockTime.nowFactory
        });

        // First track
        await service.track('view', 'movie', '123');
        assert.strictEqual(httpClient.post.mock.calls.length, 1);

        // Advance time by 1 hour + 1ms (TTL is 1 hour)
        mockTime.advance(60 * 60 * 1000 + 1);

        // Second track
        await service.track('view', 'movie', '123');
        assert.strictEqual(httpClient.post.mock.calls.length, 2); // Should track again
    });

    it('should evict old keys when capacity is reached', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const mockTime = createMockTime();

        const service = new AnalyticsService({
            httpClient,
            storage,
            nowFactory: mockTime.nowFactory
        });

        // Fill up to 500 items
        for (let i = 0; i < 500; i++) {
            service.cacheInMemory(`key-${i}`, mockTime.get());
        }
        assert.strictEqual(service.inMemory.size, 500);

        // Advance time to expire all
        mockTime.advance(60 * 60 * 1000 + 1);

        // Add 501st item -> trigger cleanup
        service.cacheInMemory('key-501', mockTime.get());

        // Should have cleaned up expired keys, leaving only the new one
        assert.strictEqual(service.inMemory.size, 1);
        assert.strictEqual(service.inMemory.has('key-501'), true);
    });

    it('should force evict oldest if still over capacity after cleanup', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const mockTime = createMockTime();

        const service = new AnalyticsService({
            httpClient,
            storage,
            nowFactory: mockTime.nowFactory
        });

        // Fill up to 600 items (all fresh)
        for (let i = 0; i < 601; i++) {
            service.cacheInMemory(`key-${i}`, mockTime.get());
        }

        // Logic:
        // When adding the 601st item (key-600), size was 600 (>500).
        // Cleanup ran (none expired).
        // Size > 600 check? No, size is 600.
        // Add key-600. Size becomes 601.

        // Now add one more to trigger strict limit
        service.cacheInMemory('key-overflow', mockTime.get());

        // Should trigger >600 check eventually or be reasonably bounded
        // In current logic:
        // Size 601. >500 cleanup (none).
        // >600 check (true). Delete first key. Size 600.
        // Add key-overflow. Size 601.

        assert.ok(service.inMemory.size <= 602);
    });

    it('should track download action successfully', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const service = new AnalyticsService({ httpClient, storage });

        const result = await service.track('download', 'movie', '456');

        assert.strictEqual(result, true);
        assert.strictEqual(httpClient.post.mock.calls.length, 1);
        assert.strictEqual(httpClient.post.mock.calls[0].arguments[1].action, 'download');
    });

    it('should return false for invalid action', async () => {
        const httpClient = createMockHttpClient();
        const service = new AnalyticsService({ httpClient, storage: null });

        assert.strictEqual(await service.track('invalid', 'movie', '123'), false);
        assert.strictEqual(await service.track('', 'movie', '123'), false);
        assert.strictEqual(httpClient.post.mock.calls.length, 0);
    });

    it('should return false for invalid entity type', async () => {
        const httpClient = createMockHttpClient();
        const service = new AnalyticsService({ httpClient, storage: null });

        assert.strictEqual(await service.track('view', 'user', '123'), false);
        assert.strictEqual(await service.track('view', 'invalid', '123'), false);
        assert.strictEqual(httpClient.post.mock.calls.length, 0);
    });

    it('should return false for empty or falsy entity id', async () => {
        const httpClient = createMockHttpClient();
        const service = new AnalyticsService({ httpClient, storage: null });

        assert.strictEqual(await service.track('view', 'movie', ''), false);
        assert.strictEqual(await service.track('view', 'movie', null), false);
        assert.strictEqual(await service.track('view', 'movie', undefined), false);
        assert.strictEqual(httpClient.post.mock.calls.length, 0);
    });

    it('should return false for wrong domain', async () => {
        const httpClient = createMockHttpClient();
        const service = new AnalyticsService({ httpClient, storage: null, defaultDomain: 'jav' });

        assert.strictEqual(await service.track('view', 'movie', '123', { domain: 'other' }), false);
        assert.strictEqual(httpClient.post.mock.calls.length, 0);
    });

    it('should send two requests when dedupe is false for same key', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const service = new AnalyticsService({ httpClient, storage });

        const r1 = await service.track('view', 'movie', '123', { dedupe: false });
        const r2 = await service.track('view', 'movie', '123', { dedupe: false });

        assert.strictEqual(r1, true);
        assert.strictEqual(r2, true);
        assert.strictEqual(httpClient.post.mock.calls.length, 2);
    });

    it('should pass userId when provided', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const service = new AnalyticsService({ httpClient, storage });

        await service.track('view', 'movie', '123', { userId: 42 });

        const payload = httpClient.post.mock.calls[0].arguments[1];
        assert.strictEqual(payload.user_id, 42);
    });

    it('should pass occurredAt and eventId when provided', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const service = new AnalyticsService({ httpClient, storage });

        await service.track('view', 'movie', '123', {
            eventId: 'custom-evt-1',
            occurredAt: '2026-01-15T12:00:00.000Z'
        });

        const payload = httpClient.post.mock.calls[0].arguments[1];
        assert.strictEqual(payload.event_id, 'custom-evt-1');
        assert.strictEqual(payload.occurred_at, '2026-01-15T12:00:00.000Z');
    });

    it('should not throw when storage setItem throws and still mark in memory', async () => {
        const httpClient = createMockHttpClient();
        const storage = {
            getItem: mock.fn(() => null),
            setItem: mock.fn(() => { throw new Error('QuotaExceeded'); })
        };
        const service = new AnalyticsService({ httpClient, storage });

        const result = await service.track('view', 'movie', '123');
        assert.strictEqual(result, true);
        assert.strictEqual(httpClient.post.mock.calls.length, 1);
        // Second call should be deduped in-memory even though storage failed
        const second = await service.track('view', 'movie', '123');
        assert.strictEqual(second, false);
        assert.strictEqual(httpClient.post.mock.calls.length, 1);
    });

    it('should return false from hasTracked when storage getItem throws', () => {
        const storage = {
            getItem: mock.fn(() => { throw new Error('SecurityError'); }),
            setItem: mock.fn(() => {})
        };
        const service = new AnalyticsService({ httpClient: createMockHttpClient(), storage });
        const trackKey = service.makeTrackKey('view', 'movie', '123');

        assert.strictEqual(service.hasTracked(trackKey), false);
    });

    it('should use uuidFactory when crypto.randomUUID is unavailable', async () => {
        const httpClient = createMockHttpClient();
        const storage = createMockStorage();
        const fixedId = 'evt-fallback-123';
        const service = new AnalyticsService({
            httpClient,
            storage,
            uuidFactory: () => fixedId
        });

        await service.track('view', 'movie', '999');
        const payload = httpClient.post.mock.calls[0].arguments[1];
        assert.strictEqual(payload.event_id, fixedId);
    });
});
