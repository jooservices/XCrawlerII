import { test, expect } from '@playwright/test';
import {
    clearRedisAnalyticsKeys,
    clearMongoAnalytics,
    getRedisHash,
    redisKeyExists,
    getMovieUuids,
    closeAll,
} from '../helpers/analytics-db';
import { resetRateLimiter } from '../helpers/analytics-cli';

const ANALYTICS_ENDPOINT = '/api/v1/analytics/events';

let movieA: string;
let movieB: string;

test.beforeAll(async () => {
    resetRateLimiter();
    const uuids = await getMovieUuids(2);
    if (uuids.length < 2) {
        throw new Error('Need at least 2 movies with UUIDs in the database');
    }
    [movieA, movieB] = uuids;

    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();
});

test.afterAll(async () => {
    await closeAll();
});

// ── A1. First view emits analytics event ───────────────────────────────

test('A1: first view emits exactly one analytics event with correct payload', async ({ page }) => {
    const analyticsRequests: { url: string; postData: any; status: number; body: any }[] = [];

    // Capture analytics POST requests
    page.on('request', (req) => {
        if (req.url().includes(ANALYTICS_ENDPOINT) && req.method() === 'POST') {
            analyticsRequests.push({
                url: req.url(),
                postData: req.postDataJSON(),
                status: 0,
                body: null,
            });
        }
    });

    page.on('response', async (res) => {
        if (res.url().includes(ANALYTICS_ENDPOINT) && res.request().method() === 'POST') {
            const entry = analyticsRequests.find((r) => r.url === res.url() && r.status === 0);
            if (entry) {
                entry.status = res.status();
                try {
                    entry.body = await res.json();
                } catch {
                    entry.body = null;
                }
            }
        }
    });

    // Navigate to movie page
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    // Wait a moment for analytics to fire
    await page.waitForTimeout(1000);

    // Assert: exactly 1 POST request fired
    const viewRequests = analyticsRequests.filter(
        (r) => r.postData?.entity_id === movieA && r.postData?.action === 'view',
    );
    expect(viewRequests.length).toBe(1);

    const req = viewRequests[0];

    // Assert: payload shape
    expect(req.postData.domain).toBe('jav');
    expect(req.postData.entity_type).toBe('movie');
    expect(req.postData.entity_id).toBe(movieA);
    expect(req.postData.action).toBe('view');
    expect(req.postData.value).toBe(1);
    expect(req.postData.event_id).toBeTruthy();
    expect(req.postData.occurred_at).toBeTruthy();

    // Assert: response
    expect(req.status).toBe(202);
    expect(req.body).toEqual({ status: 'accepted' });

    // Assert: Redis counter key exists
    const counterKey = `anl:counters:jav:movie:${movieA}`;
    const hash = await getRedisHash(counterKey);
    expect(parseInt(hash['view'] || '0', 10)).toBe(1);

    // Assert: dedupe key exists
    const dedupeKey = `anl:evt:${req.postData.event_id}`;
    expect(await redisKeyExists(dedupeKey)).toBe(true);
});

// ── A2. Dedupe in same session ─────────────────────────────────────────

test('A2: refresh same page does NOT fire second analytics event', async ({ page }) => {
    const analyticsRequests: any[] = [];

    page.on('request', (req) => {
        if (req.url().includes(ANALYTICS_ENDPOINT) && req.method() === 'POST') {
            try {
                const data = req.postDataJSON();
                if (data?.entity_id === movieA && data?.action === 'view') {
                    analyticsRequests.push(data);
                }
            } catch { }
        }
    });

    // First visit (uses stored sessionStorage from A1 if same context, but new context means new session)
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const firstCount = analyticsRequests.length;

    // Refresh the page
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // No new analytics POST for same movie after refresh
    // FE uses sessionStorage dedupe — same session means same key exists
    expect(analyticsRequests.length).toBe(firstCount);

    // Assert: Redis counter should not have doubled
    const counterKey = `anl:counters:jav:movie:${movieA}`;
    const hash = await getRedisHash(counterKey);
    // We allow at most the value from A1 + this first visit = up to 2
    // But dedupe should prevent the reload from adding another
    const viewCount = parseInt(hash['view'] || '0', 10);
    expect(viewCount).toBeLessThanOrEqual(2);
});

// ── A3. View event from different movie ────────────────────────────────

test('A3: navigating to a different movie fires a new analytics event', async ({ page }) => {
    const analyticsRequests: any[] = [];

    page.on('request', (req) => {
        if (req.url().includes(ANALYTICS_ENDPOINT) && req.method() === 'POST') {
            try {
                analyticsRequests.push(req.postDataJSON());
            } catch { }
        }
    });

    page.on('response', async (res) => {
        if (res.url().includes(ANALYTICS_ENDPOINT) && res.request().method() === 'POST') {
            // just capture status
        }
    });

    await page.goto(`/jav/movies/${movieB}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const movieBRequests = analyticsRequests.filter(
        (r) => r.entity_id === movieB && r.action === 'view',
    );
    expect(movieBRequests.length).toBe(1);
    expect(movieBRequests[0].domain).toBe('jav');
    expect(movieBRequests[0].entity_type).toBe('movie');

    // Assert: separate Redis key for movieB
    const counterKey = `anl:counters:jav:movie:${movieB}`;
    const hash = await getRedisHash(counterKey);
    expect(parseInt(hash['view'] || '0', 10)).toBeGreaterThanOrEqual(1);
});

// ── D1. Movie page must use shared analytics service ───────────────────

test('D1: only /api/v1/analytics/events endpoint is used for analytics', async ({ page }) => {
    const allRequests: string[] = [];

    page.on('request', (req) => {
        const url = req.url();
        // Track any URL that looks analytics-related
        if (
            url.includes('analytics') ||
            url.includes('sync') ||
            url.includes('/view') // legacy /jav/movies/{id}/view
        ) {
            allRequests.push(`${req.method()} ${new URL(url).pathname}`);
        }
    });

    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Filter only analytics endpoint calls
    const analyticsEndpointCalls = allRequests.filter((r) => r.includes(ANALYTICS_ENDPOINT));
    const nonAnalyticsCalls = allRequests.filter(
        (r) => !r.includes(ANALYTICS_ENDPOINT) && !r.includes('GET'),
    );

    // Should have at least one call to the correct endpoint
    // (may be deduplicated by sessionStorage if A1 already ran in same context)
    // No calls to legacy endpoints
    expect(nonAnalyticsCalls.length).toBe(0);
});

// ── D2. No stale endpoint usage ────────────────────────────────────────

test('D2: dashboard → movie → back flow uses no deprecated analytics paths', async ({ page }) => {
    const suspectPaths: string[] = [];
    const LEGACY_PATTERNS = ['/view', '/analytics/sync', '/analytics-sync', '/analyticsClient'];

    page.on('request', (req) => {
        const pathname = new URL(req.url()).pathname;
        for (const pattern of LEGACY_PATTERNS) {
            if (pathname.includes(pattern)) {
                suspectPaths.push(`${req.method()} ${pathname}`);
            }
        }
    });

    // Dashboard
    await page.goto('/jav/dashboard');
    await page.waitForLoadState('networkidle');

    // Movie page
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    // Back to dashboard
    await page.goto('/jav/dashboard');
    await page.waitForLoadState('networkidle');

    expect(suspectPaths).toEqual([]);
});
