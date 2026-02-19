
import { test, expect } from '@playwright/test';
import {
    clearRedisAnalyticsKeys,
    getMongoTotals,
    getMovieUuids,
    closeAll,
} from '../helpers/analytics-db';
import { runFlush, resetRateLimiter } from '../helpers/analytics-cli';

let targetMovieUuid: string;

test.beforeAll(async () => {
    // 1. Reset everything to ensure a clean state
    resetRateLimiter();

    // Force clear Redis keys using CLI to be absolutely sure
    try {
        const { execSync } = require('child_process');
        execSync("redis-cli --scan --pattern 'laravel-database-anl:*' | xargs redis-cli DEL", { stdio: 'inherit' });
        console.log('[Demo] Force-cleared all analytics keys via redis-cli');
    } catch (e) {
        console.warn('[Demo] Failed to force-clear Redis keys (might be empty):', e.message);
    }

    // 2. Get a real movie from MySQL to visit
    const uuids = await getMovieUuids(1);
    if (uuids.length < 1) {
        throw new Error('Database is empty! Need at least 1 movie in MySQL.');
    }
    targetMovieUuid = uuids[0];

    console.log(`[Demo] Target Movie UUID: ${targetMovieUuid}`);
});

test.afterAll(async () => {
    await closeAll();
});

test('Demonstration: User views a movie, system counts it', async ({ page }) => {
    // ── Step 1: Check initial view count ─────────────────────────────────────
    let initialViews = 0;
    await test.step('1. Check initial view count in MongoDB', async () => {
        const totals = await getMongoTotals(targetMovieUuid);
        initialViews = totals?.view || 0;
        console.log(`[Demo] Initial MongoDB View Count: ${initialViews}`);
    });

    // ── Step 2: User visits the movie page ───────────────────────────────────
    await test.step('2. User visits the movie page', async () => {
        const url = `/jav/movie/${targetMovieUuid}`;
        console.log(`[Demo] Navigating to ${url}`);

        // Monitor network traffic for analytics requests
        page.on('request', request => {
            if (request.url().includes('/analytics/events')) {
                console.log(`[Demo] >> Request: ${request.method()} ${request.url()}`);
                console.log(`[Demo]    Payload: ${request.postData()}`);
            }
        });

        page.on('response', response => {
            if (response.url().includes('/analytics/events')) {
                console.log(`[Demo] << Response: ${response.status()} ${response.statusText()}`);
            }
        });

        // Go to the JAV Dashboard
        const dashboardUrl = '/jav/dashboard';
        console.log(`[Demo] Navigating to Dashboard: ${dashboardUrl}`);
        await page.goto(dashboardUrl);
        await page.waitForLoadState('networkidle');

        // Find a movie card and click it
        // We look for a link that goes to /jav/movies/...
        const movieLink = page.locator('a[href^="/jav/movies/"]').first();
        if (await movieLink.count() === 0) {
            // Fallback: direct navigation if dashboard has no links (e.g. empty)
            console.warn('[Demo] No movie links found on dashboard. Falling back to direct navigation.');
            const url = `/jav/movies/${targetMovieUuid}`;
            console.log(`[Demo] Navigating directly to ${url}`);
            await page.goto(url);
        } else {
            // Get the target URL from the link to extract UUID for DB verification
            const targetUrl = await movieLink.getAttribute('href');
            console.log(`[Demo] Found movie link: ${targetUrl}`);

            const uuidMatch = targetUrl?.match(/\/jav\/movies\/([a-f0-9-]+)/);
            if (uuidMatch) {
                targetMovieUuid = uuidMatch[1];
                console.log(`[Demo] Selected Movie UUID from UI: ${targetMovieUuid}`);
            }

            // Log initial count for this specific movie
            const totals = await getMongoTotals(targetMovieUuid);
            initialViews = totals?.view || 0;
            console.log(`[Demo] Initial MongoDB View Count for ${targetMovieUuid}: ${initialViews}`);

            // Click the movie
            console.log('[Demo] Clicking movie card...');
            await movieLink.click();
        }

        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');

        // Explicitly wait for the analytics request to complete
        try {
            const analyticsResponse = await page.waitForResponse(resp =>
                resp.url().includes('/analytics/events') && resp.status() === 202,
                { timeout: 5000 }
            );
            console.log('[Demo] Analytics request confirmed success (202)');
        } catch (e) {
            console.warn('[Demo] WARNING: specific analytics 202 response check timed out within 5s');
        }

        // Take a screenshot of the movie page
        await page.screenshot({ path: 'demonstration-step2-movie-page.png', fullPage: true });
        console.log('[Demo] Screenshot taken: demonstration-step2-movie-page.png');
    });

    // ── Step 3: Wait for analytics event to be sent ──────────────────────────
    await test.step('3. Wait for background analytics event', async () => {
        // The frontend sends the event. It might take a moment.
        // We can verify the request was sent by intercepting, but for this demo
        // we just wait a bit to be sure it reached the server.
        await page.waitForTimeout(2000);
    });

    // ── Step 4: Run the schedule/flush command ───────────────────────────────
    await test.step('4. Run analytics flush command', async () => {
        console.log('[Demo] Running php artisan analytics:flush...');
        const result = runFlush();

        if (result.exitCode !== 0) {
            // It's possible flush returns error if some keys are malformed, but we check output
            console.warn('[Demo] Flush had errors/warnings:', result.stderr);
        }

        console.log(`[Demo] Flush output: ${result.stdout.trim()}`);
        // We expect at least 1 key processed
        expect(result.keysProcessed).toBeGreaterThanOrEqual(1);
    });

    // ── Step 5: Verify view count increased ──────────────────────────────────
    await test.step('5. Verify view count increased in MongoDB', async () => {
        const finalTotals = await getMongoTotals(targetMovieUuid);
        const finalViews = finalTotals?.view || 0;

        console.log(`[Demo] Final MongoDB View Count: ${finalViews}`);

        // Assertion: Count should have increased
        expect(finalViews).toBeGreaterThan(initialViews);

        console.log(`[Demo] SUCCESS: View count passed from ${initialViews} to ${finalViews} (+${finalViews - initialViews})`);
    });
});
