import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect } from './fixtures.js';

const FILES_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../files');

export const testFile = (name) => path.join(FILES_DIR, name);

export async function login(page, { username, password }) {
    await page.goto('/login');
    await page.locator('#inputUsername').fill(username);
    await page.locator('#inputPassword').fill(password);
    await page.locator('#_submit').click();
}

export async function createSeries(page, title) {
    await page.goto('/admin/series/');
    await page.locator('#pumukitnewadmin_series_new_button').click();
    const titleInput = page.locator('#pumukitnewadmin_series_i18n_title_en');
    await expect(titleInput).toBeVisible();
    const id = await page.locator('#inputROId').inputValue();
    await titleInput.fill(title);
    await page.locator('#pumukitnewadmin_series_updatemeta_cancel ~ button[type=submit]').click();
    await expect(page.locator('#list_series').getByRole('link', { name: title, exact: true })).toHaveAttribute('href', `/admin/mms/series?id=${id}`);

    return id;
}

export async function uploadToSeries(page, seriesId, file) {
    await page.goto(`/admin/wizard/${seriesId}/upload`);
    const imported = page.waitForResponse((response) => response.url().endsWith('/dispatchImport') && response.ok());
    await page.locator('.uppy-Dashboard-input').first().setInputFiles(testFile(file));
    expect(await (await imported).json()).toEqual({ success: true });
    await expect(async () => {
        await page.goto(`/admin/mms/series?id=${seriesId}`);
        await expect(page.locator('#list_mms tbody input[type=checkbox]')).toHaveCount(1, { timeout: 1_000 });
    }).toPass({ timeout: 60_000, intervals: [1_000, 2_000] });
}

export async function openMultimediaObject(page, seriesId, text) {
    await page.goto(`/admin/mms/series?id=${seriesId}`);
    const row = page.locator('tr', { hasText: text });
    await expect(row).toHaveCount(1);
    const id = await row.locator('input[type=checkbox]').getAttribute('id');
    await row.locator('td', { hasText: text }).click();
    await expect(page.locator('#mm_id_pub')).toHaveValue(id);
    await page.waitForLoadState('networkidle');

    return id;
}

export async function expectTrack(page, seriesId, text, profile, tag) {
    let id;
    await expect(async () => {
        id = await openMultimediaObject(page, seriesId, text);
        await page.locator('a[href="#mediaMm"]').click();
        const track = page.locator(`#tracks_mms_${id} tr`).filter({ hasText: ` - ${profile} (` });
        await expect(track).toHaveCount(1, { timeout: 2_000 });
        if (tag) {
            await expect(track).toContainText(tag, { timeout: 1_000 });
        }
    }).toPass({ timeout: 90_000, intervals: [2_000, 5_000] });

    return id;
}
