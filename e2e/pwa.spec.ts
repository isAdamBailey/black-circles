import { test, expect } from '@playwright/test';

test.describe('PWA', () => {
    test('serves a web app manifest with icons and theme color', async ({ request }) => {
        const response = await request.get('/manifest.webmanifest');

        expect(response.ok()).toBeTruthy();
        expect(response.headers()['content-type']).toMatch(/application\/manifest\+json|application\/json/);

        const manifest = await response.json();
        expect(manifest.name).toBe('Black Circles');
        expect(manifest.short_name).toBe('Black Circles');
        expect(manifest.display).toBe('standalone');
        expect(manifest.theme_color).toBe('#030712');
        expect(manifest.icons).toEqual(
            expect.arrayContaining([
                expect.objectContaining({ src: expect.stringContaining('pwa-192x192.png'), sizes: '192x192' }),
                expect.objectContaining({ src: expect.stringContaining('pwa-512x512.png'), sizes: '512x512' }),
                expect.objectContaining({
                    src: expect.stringContaining('pwa-maskable-512x512.png'),
                    purpose: 'maskable',
                }),
            ]),
        );
    });

    test('serves the service worker script', async ({ request }) => {
        const response = await request.get('/sw.js');

        expect(response.ok()).toBeTruthy();
        expect(await response.text()).toMatch(/precache|workbox/i);
    });

    test('home page links the web app manifest', async ({ page }) => {
        await page.goto('/');

        const manifest = page.locator('link[rel="manifest"]');
        await expect(manifest).toHaveAttribute('href', /manifest\.webmanifest/);
    });
});
