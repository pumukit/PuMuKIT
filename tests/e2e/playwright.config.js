import { defineConfig, devices } from '@playwright/test';

export const ADMIN_STATE = '.auth/admin.json';

export default defineConfig({
    testDir: './tests',
    timeout: 120_000,
    expect: { timeout: 15_000 },
    fullyParallel: false,
    workers: 1,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: process.env.BASE_URL ?? 'https://localhost',
        ignoreHTTPSErrors: true,
        locale: 'en-US',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.js/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'], storageState: ADMIN_STATE },
            dependencies: ['setup'],
        },
    ],
});
