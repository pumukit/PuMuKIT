import { login } from '../support/admin.js';
import { ADMIN, expect, test } from '../support/fixtures.js';

test.describe('login', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('anonymous users are sent to the login page from the back-office', async ({ page }) => {
        await page.goto('/admin/series/');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('unknown credentials are rejected', async ({ page }) => {
        await login(page, { username: 'e2e-nobody', password: 'wrong' });
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByText('Invalid credentials or account temporarily blocked.')).toBeVisible();
    });

    test('an administrator logs in, reaches the back-office and logs out', async ({ page }) => {
        await login(page, ADMIN);
        await page.getByRole('link', { name: 'Go to back-office' }).click();
        await expect(page.getByRole('button', { name: 'Media Manager' })).toBeVisible();

        await page.goto('/');
        await page.getByRole('link', { name: 'Log out' }).click();
        await page.goto('/admin/series/');
        await expect(page).toHaveURL(/\/login$/);
    });
});
