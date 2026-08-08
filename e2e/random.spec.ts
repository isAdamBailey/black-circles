import { test, expect } from '@playwright/test';

test.describe('Random', () => {
    test('redirects to a release detail page', async ({ page }) => {
        await page.goto('/random');

        await expect(page).toHaveURL(/\/collection\/\d+$/);
        await expect(page.locator('h1, h2').first()).toBeVisible();
    });
});
