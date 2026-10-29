import { ADMIN_STATE } from '../playwright.config.js';
import { login } from '../support/admin.js';
import { ADMIN, expect, test } from '../support/fixtures.js';

test('the application answers', async ({ request }) => {
    await expect.poll(async () => (await request.get('/login')).status(), { timeout: 180_000, intervals: [2_000] }).toBe(200);
});

test('log in as administrator', async ({ page }) => {
    await login(page, ADMIN);
    await expect(page.getByRole('link', { name: 'Go to back-office' })).toBeVisible();
    await page.context().storageState({ path: ADMIN_STATE });
});
