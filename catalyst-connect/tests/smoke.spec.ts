import { test, expect } from '@playwright/test';

const APP_BASE = '/public/frontend';

test.beforeEach(async ({ page }) => {
  await page.route('**/api/instruments**', async (route) => {
    const url = route.request().url();

    if (url.endsWith('/api/instruments')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [
            {
              id: 'I1',
              name: 'Test Microscope',
              status: 'available',
              category: 'Microscope',
              description: 'Test microscope for QA',
              location: 'Lab 101',
              image: '/placeholder.svg',
              usage_cost: '$20/hr',
            },
          ],
        }),
      });
      return;
    }

    if (url.includes('/api/instruments/')) {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            id: 'I1',
            name: 'Test Microscope',
            status: 'available',
            category: 'Microscope',
            description: 'Test microscope for QA',
            location: 'Lab 101',
            image: '/placeholder.svg',
            usage_cost: '$20/hr',
          },
        }),
      });
      return;
    }

    await route.fallback();
  });
});

test('protected routes redirect unauthenticated users to login', async ({ page }) => {
  await page.goto(`${APP_BASE}/bag`);

  await expect(page.getByRole('heading', { name: 'Login' })).toBeVisible();
  await expect(page).toHaveURL(/\/public\/frontend\/login$/);
});

test('home -> instrument details -> bag -> booking form stays put on invalid email', async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem('auth_token', 'test-token');
    window.localStorage.setItem('otp_verified', 'true');
    window.localStorage.setItem('is_admin', 'false');
    window.localStorage.setItem(
      'auth_user',
      JSON.stringify({
        id: 'U1',
        name: 'Test User',
        email: 'test@example.com',
      }),
    );
  });

  await page.route('**/api/check-availability**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        available: true,
      }),
    });
  });

  await page.route('**/api/lock-slot', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true }),
    });
  });

  await page.route('**/api/release-lock', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true }),
    });
  });

  await page.goto(`${APP_BASE}/`);

  await expect(page.getByRole('heading', { name: /Precision Instrumentation/i })).toBeVisible();
  await expect(page.getByRole('link', { name: /Browse Instruments/i })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Test Microscope' })).toBeVisible({ timeout: 15000 });

  await page.goto(`${APP_BASE}/instrument/I1`);

  await expect(page.getByRole('heading', { name: 'Test Microscope' })).toBeVisible({ timeout: 10000 });

  const fromInput = page.locator('input[placeholder="YYYY-MM-DD"]').nth(0);
  const toInput = page.locator('input[placeholder="YYYY-MM-DD"]').nth(1);
  await expect(page.locator('text=Add to Booking Bag')).toBeVisible();

  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(today.getDate() + 1);

  const d1 = today.toISOString().split('T')[0];
  const d2 = tomorrow.toISOString().split('T')[0];

  await fromInput.fill(d1);
  await toInput.fill(d2);

  await page.locator('button:has-text("Bag & Review")').click();

  await expect(page.getByRole('heading', { name: 'Booking Bag' })).toBeVisible();

  await page.locator('a:has-text("Proceed to Booking")').click();
  await expect(page.getByRole('heading', { name: 'Booking Request' })).toBeVisible();

  await page.fill('input#name', 'Test User');
  await page.fill('input#email', 'invalid-email');
  await page.fill('input#enrollment', 'ENR12345');
  await page.fill('input#dept', 'QA Department');

  await page.locator('button:has-text("Submit Booking Request")').click();

  await expect(page).toHaveURL(/\/public\/frontend\/booking-form$/);
});
