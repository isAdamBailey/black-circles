import { test, expect } from '@playwright/test';

test.describe('Vibe search', () => {
    test('submitting a prompt lands on a suggestion page', async ({ page }) => {
        await page.goto('/');

        await page.getByPlaceholder(/dark moody post-punk/i).fill('dark moody post-punk for a late night drive');
        await page.getByRole('button', { name: 'Find it' }).click();

        // Inertia lands on a clean /vibe URL (server-driven POST navigation);
        // the Nuxt page reads the prompt from ?prompt= so it stays in the URL.
        await expect(page).toHaveURL(/\/vibe(\?|$)/);
        await expect(page.getByRole('heading', { level: 2, name: 'Night Drive' })).toBeVisible();
        await expect(page.getByText('Nocturne')).toBeVisible();
    });
});
