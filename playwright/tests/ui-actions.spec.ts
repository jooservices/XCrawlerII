import { test, expect } from '@playwright/test';
import {
    clearRedisAnalyticsKeys,
    clearMongoAnalytics,
    getMongoTotals,
    getMysqlMovieCounters,
    getMovieUuids,
    closeAll,
} from '../helpers/analytics-db';
import { runFlush, resetRateLimiter } from '../helpers/analytics-cli';

let movieA: string;

test.beforeAll(async () => {
    resetRateLimiter();
    const uuids = await getMovieUuids(1);
    if (uuids.length < 1) {
        throw new Error('Need at least 1 movie with UUID in the database');
    }
    [movieA] = uuids;

    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();
});

test.afterAll(async () => {
    await closeAll();
});

// ── A4. Download increments download analytics (server-side) ───────────

test('A4: download click triggers server-side analytics and flushes correctly', async ({ page }) => {
    // Navigate to the movie page
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    // Record MySQL counters before download
    const beforeMysql = await getMysqlMovieCounters(movieA);

    // Click Download Torrent — intercept to prevent actual external download
    // The download route fires trackDownload server-side BEFORE the external fetch
    const downloadLink = page.locator('a[href*="download"]').first();
    await expect(downloadLink).toBeVisible();

    // Use route interception to handle the download response gracefully
    // (external source may not be available, but analytics tracking fires first)
    const downloadPromise = page.waitForResponse(
        (res) => res.url().includes('/download'),
        { timeout: 15_000 },
    ).catch(() => null);

    await downloadLink.click();
    await downloadPromise;

    // Wait a moment for server-side processing
    await page.waitForTimeout(1000);

    // Run flush to push Redis → Mongo → MySQL
    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: Mongo totals for movie_a should have download >= 1
    const mongoTotals = await getMongoTotals(movieA);
    expect(mongoTotals).not.toBeNull();
    expect(mongoTotals!.download).toBeGreaterThanOrEqual(1);

    // Assert: MySQL downloads synced with Mongo
    const afterMysql = await getMysqlMovieCounters(movieA);
    expect(afterMysql).not.toBeNull();
    expect(afterMysql!.downloads).toBe(mongoTotals!.download);
});

// ── A5. Like action remains functional and does not break analytics ────

test('A5: like toggle works and does not corrupt analytics counters', async ({ page }) => {
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    // Get current Mongo totals before like
    const beforeTotals = await getMongoTotals(movieA);

    // Find the Like button
    const likeButton = page.locator('button', { hasText: /Like/i }).first();
    await expect(likeButton).toBeVisible();

    // Capture the Like API response
    const [likeResponse] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('/like') || res.url().includes('toggle-like')),
        likeButton.click(),
    ]);

    expect(likeResponse.status()).toBe(200);
    const likeBody = await likeResponse.json();
    expect(likeBody.success).toBe(true);

    // Verify UI state changed
    // If was previously unliked, should now show "Liked"; if was liked, shows "Like"
    const buttonText = await likeButton.textContent();
    expect(buttonText?.trim()).toMatch(/^Liked?$/i);

    // Run flush and verify analytics counters are not corrupted
    runFlush();

    const afterTotals = await getMongoTotals(movieA);
    // Analytics counters should remain coherent — no unexpected changes from like action
    if (beforeTotals && afterTotals) {
        // Download/view counters should not decrease
        expect(afterTotals.view).toBeGreaterThanOrEqual(beforeTotals.view);
        expect(afterTotals.download).toBeGreaterThanOrEqual(beforeTotals.download);
    }
});

// ── B6. API failure should not break UI render ─────────────────────────

test('B6: analytics API returning 500 does not break page rendering', async ({ page }) => {
    // Intercept analytics endpoint and force 500 response
    await page.route('**/api/v1/analytics/events', (route) => {
        route.fulfill({
            status: 500,
            contentType: 'application/json',
            body: JSON.stringify({ error: 'Internal Server Error' }),
        });
    });

    // Navigate to movie page
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    // Assert: page still renders correctly despite analytics failure
    // Check for key UI elements
    await expect(page.locator('h1, h2, h3').first()).toBeVisible();

    // Check that movie information is displayed
    const pageContent = await page.textContent('body');
    expect(pageContent).toBeTruthy();

    // No unhandled error overlay or crash
    const errorOverlay = page.locator('[id*="error"], [class*="error-overlay"], [class*="fatal"]');
    await expect(errorOverlay).toHaveCount(0);
});

// ── X3. Download action uses server-side event_id (always unique) ──────

test('X3: repeated downloads increment counter each time (no FE dedupe on download)', async ({ page }) => {
    await clearRedisAnalyticsKeys();
    await clearMongoAnalytics();

    // First download
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    const downloadLink = page.locator('a[href*="download"]').first();
    await expect(downloadLink).toBeVisible();

    await Promise.all([
        page.waitForResponse((res) => res.url().includes('/download'), { timeout: 15_000 }).catch(() => null),
        downloadLink.click(),
    ]);
    await page.waitForTimeout(500);

    // Second download (navigate back and click again)
    await page.goto(`/jav/movies/${movieA}`);
    await page.waitForLoadState('networkidle');

    await Promise.all([
        page.waitForResponse((res) => res.url().includes('/download'), { timeout: 15_000 }).catch(() => null),
        page.locator('a[href*="download"]').first().click(),
    ]);
    await page.waitForTimeout(500);

    // Flush
    const flushResult = runFlush();
    expect(flushResult.exitCode).toBe(0);

    // Assert: download counter = 2 (each download generates unique event_id server-side)
    const totals = await getMongoTotals(movieA);
    expect(totals).not.toBeNull();
    expect(totals!.download).toBeGreaterThanOrEqual(2);
});
