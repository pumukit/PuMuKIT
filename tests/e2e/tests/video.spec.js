import { createSeries, expectTrack, openMultimediaObject, uploadToSeries } from '../support/admin.js';
import { expect, test } from '../support/fixtures.js';

test('a video is uploaded, transcoded, edited, published and played in the portal', async ({ page, uniqueTitle }) => {
    const seriesTitle = uniqueTitle('E2E series');
    const videoTitle = uniqueTitle('E2E video');
    let seriesId;
    let videoId;

    await test.step('create a series', async () => {
        seriesId = await createSeries(page, seriesTitle);
    });

    await test.step('upload a video as master copy', async () => {
        await uploadToSeries(page, seriesId, 'pumukit.mp4');
        videoId = await expectTrack(page, seriesId, '_pumukit_mp4', 'master_copy');
    });

    await test.step('edit the metadata', async () => {
        await page.locator('a[href="#metaMm"]').click();
        await page.locator('#pumukitnewadmin_mms_meta_i18n_title_en').fill(videoTitle);
        await page.locator('#pumukitnewadmin_mms_meta_i18n_description_en').fill('Uploaded by the end-to-end tests.');
        const saved = page.waitForResponse((response) => response.url().endsWith(`/admin/mms/${videoId}/update/meta`) && response.ok());
        await page.locator('#metaMm button[type=submit]').click();
        await saved;
        await expect(page.locator('tr', { hasText: videoTitle })).toHaveCount(1);
    });

    await test.step('publish it in the WebTV, which transcodes a broadcastable track', async () => {
        await openMultimediaObject(page, seriesId, videoTitle);
        await page.locator('a[href="#pubMm"]').click();
        await page.locator('#pumukitnewadmin_mms_pub_status').selectOption({ label: 'Published' });
        await page.locator('#pub_channels_PUCHWEBTV').check();
        await expect(page.locator('#pumukitnewadmin_mms_pub_status')).toHaveValue('0');
        const saved = page.waitForResponse((response) => response.url().endsWith(`/admin/mms/${videoId}/update/pub`) && response.ok());
        await page.locator('#pubMm button[type=submit]').click();
        await saved;
        await expectTrack(page, seriesId, videoTitle, 'video_broadcastable', 'display');
    });

    await test.step('find it in the portal', async () => {
        await page.goto(`/searchmultimediaobjects?search=${encodeURIComponent(videoTitle.split(' ').pop())}`);
        await expect(page.locator(`a[href$="/video/${videoId}"]`).first()).toBeVisible();

        await page.goto(`/video/${videoId}`);
        await expect(page.getByRole('heading', { level: 1, name: videoTitle })).toBeVisible();
        await expect(page.getByText('Uploaded by the end-to-end tests.')).toBeVisible();
    });

    await test.step('play it', async () => {
        await page.goto(`/videoplayer/${videoId}`);
        const viewport = page.viewportSize();
        await expect(page.locator('body')).toBeVisible();
        await page.waitForLoadState('networkidle');
        await page.mouse.click(viewport.width / 2, viewport.height / 2);
        const video = page.locator('video').first();
        await expect(video).toBeAttached();
        await expect.poll(() => video.evaluate((element) => element.currentTime), { timeout: 20_000 }).toBeGreaterThan(1);
        expect(await video.evaluate((element) => element.error)).toBeNull();
    });
});
