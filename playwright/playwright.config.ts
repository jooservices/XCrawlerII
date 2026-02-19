import { defineConfig, devices } from '@playwright/test';
import path from 'path';

const STORAGE_STATE = path.join(__dirname, 'browser', 'chrome', 'storageState.json');

export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never' }]],
    timeout: 30_000,
    expect: { timeout: 5_000 },

    globalSetup: './helpers/global-setup.ts',

    use: {
        baseURL: process.env.BASE_URL || 'http://localhost:8000',
        storageState: STORAGE_STATE,
        trace: 'on-first-retry',
        screenshot: 'on',
        video: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chrome',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
