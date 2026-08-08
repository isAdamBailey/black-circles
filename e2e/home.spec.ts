import { test, expect } from '@playwright/test';

test.describe('Home', () => {
    test('renders mood chips, personality insight, and the vibe form', async ({ page }) => {
        await page.goto('/');

        await expect(page.getByRole('heading', { name: "Adam's Black Circles" })).toBeVisible();

        await expect(page.getByRole('link', { name: 'Chill' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Dark' })).toBeVisible();

        await expect(page.getByPlaceholder(/dark moody post-punk/i)).toBeVisible();
        await expect(page.getByRole('button', { name: 'Find it' })).toBeVisible();

        await expect(page.getByRole('heading', { name: "Adam's music personality" })).toBeVisible();
    });
});
