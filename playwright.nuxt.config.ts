import { defineConfig, devices } from '@playwright/test';

// Reuses the e2e/ specs against the Nuxt SPA instead of the Inertia app —
// same fixtures (DiscogsRelease/Mood data), same backend, different origin.
// The API is shared so CORS must allow this origin (see e2e CI job).
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:3000';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? 'github' : 'html',
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
