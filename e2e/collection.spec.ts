import { test, expect } from '@playwright/test';

test.describe('Collection', () => {
    test('grid renders, genre filter narrows results, and search autocomplete returns matches', async ({ page }) => {
        await page.goto('/collection');

        await expect(page.getByRole('heading', { name: "Adam's Vinyl Collection" })).toBeVisible();
        await expect(page.getByRole('link', { name: /Chill Sessions/ })).toBeVisible();
        await expect(page.getByRole('link', { name: /Electric Pulse/ })).toBeVisible();

        await page.getByRole('button', { name: /^Filter/ }).click();
        await page.getByRole('button', { name: 'Electronic', exact: true }).click();

        await expect(page.getByRole('link', { name: /Electric Pulse/ })).toBeVisible();
        await expect(page.getByRole('link', { name: /Chill Sessions/ })).toHaveCount(0);

        await page.getByPlaceholder(/Search Adam's titles/i).fill('Warm');
        await expect(page.getByText('Warm Grooves').first()).toBeVisible();
    });

    test('clicking a release opens its detail page', async ({ page }) => {
        await page.goto('/collection');

        await page.getByRole('link', { name: /Chill Sessions/ }).click();

        await expect(page).toHaveURL(/\/collection\/\d+$/);
        await expect(page.getByRole('heading', { name: 'Chill Sessions' })).toBeVisible();
    });
});
