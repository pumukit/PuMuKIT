import { test as base, expect } from '@playwright/test';

export const ADMIN = { username: process.env.E2E_ADMIN_USER ?? 'admin', password: process.env.E2E_ADMIN_PASS ?? 'admin' };

export const test = base.extend({
    context: async ({ context }, use) => {
        await context.route('**/_wdt/**', (route) => route.fulfill({ status: 200, body: '' }));
        await use(context);
    },
    uniqueTitle: async ({}, use, testInfo) => {
        await use((prefix) => `${prefix} e2e${testInfo.workerIndex}${Date.now().toString(36)}`);
    },
});

export { expect };
