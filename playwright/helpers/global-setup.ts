import { chromium, FullConfig } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const STORAGE_STATE_PATH = path.join(__dirname, '..', 'browser', 'chrome', 'storageState.json');
const PROFILE_DIR = path.join(__dirname, '..', 'browser', 'chrome');

async function globalSetup(config: FullConfig) {
    const baseURL = config.projects[0].use.baseURL || 'http://localhost:8000';

    // Ensure profile directory exists
    fs.mkdirSync(PROFILE_DIR, { recursive: true });

    // If storage state already exists and is fresh, skip login
    if (fs.existsSync(STORAGE_STATE_PATH)) {
        const stat = fs.statSync(STORAGE_STATE_PATH);
        const ageMs = Date.now() - stat.mtimeMs;
        const ONE_DAY = 24 * 60 * 60 * 1000;
        if (ageMs < ONE_DAY) {
            console.log('[global-setup] Storage state is fresh, skipping login.');
            return;
        }
    }

    console.log('[global-setup] Logging in to create persistent session...');
    console.log(`[global-setup] Target URL: ${baseURL}/login`);

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Navigate to login page with generous timeout
        console.log('[global-setup] Navigating to login page...');
        await page.goto(`${baseURL}/login`, { timeout: 60_000 });
        console.log('[global-setup] Page loaded, current URL:', page.url());

        // Wait for the login form input to appear
        console.log('[global-setup] Waiting for login form...');
        await page.waitForSelector('#login', { state: 'visible', timeout: 15_000 });
        console.log('[global-setup] Login form visible.');

        // Fill login form
        await page.locator('#login').click();
        await page.locator('#login').fill('admin');
        await page.locator('#password').click();
        await page.locator('#password').fill('password');
        console.log('[global-setup] Form filled, submitting...');

        // Submit the form — use text to distinguish from Search button in nav
        await page.getByRole('button', { name: 'Login' }).click();

        // Wait for redirect to dashboard
        await page.waitForURL('**/jav/dashboard**', { timeout: 15_000 });
        console.log('[global-setup] Login successful! URL:', page.url());

        // Save storage state (cookies + localStorage)
        await context.storageState({ path: STORAGE_STATE_PATH });
        console.log('[global-setup] Storage state saved to:', STORAGE_STATE_PATH);
    } catch (error) {
        // Take screenshot on failure for debugging
        const screenshotPath = path.join(PROFILE_DIR, 'login-failure.png');
        try {
            await page.screenshot({ path: screenshotPath, fullPage: true });
            console.error(`[global-setup] Screenshot saved to: ${screenshotPath}`);
        } catch {
            console.error('[global-setup] Could not capture screenshot');
        }

        console.error('[global-setup] Current URL:', page.url());
        console.error('[global-setup] Page title:', await page.title().catch(() => 'N/A'));
        console.error('[global-setup] Login FAILED:', error);
        throw error;
    } finally {
        await browser.close();
    }
}

export default globalSetup;
