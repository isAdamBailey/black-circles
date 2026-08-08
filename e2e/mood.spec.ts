import { test, expect } from '@playwright/test';

test.describe('Mood suggest', () => {
    test('clicking a mood shows a primary suggestion and backups', async ({ page }) => {
        await page.goto('/');

        await page.getByRole('link', { name: 'Chill' }).click();

        await expect(page).toHaveURL(/\/mood\/chill$/);
        await expect(page.getByRole('heading', { name: 'Chill', exact: true })).toBeVisible();

        // Primary pick: cover art / title link to the release detail page.
        await expect(page.getByRole('heading', { name: 'Chill Sessions' })).toBeVisible();

        await expect(page.getByText(/Also in Adam.s collection/i)).toBeVisible();
    });
});
