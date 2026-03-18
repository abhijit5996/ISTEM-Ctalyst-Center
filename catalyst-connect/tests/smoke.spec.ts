import { test, expect } from '@playwright/test';

test('home -> instrument grid -> add to bag -> booking form email required', async ({ page }) => {
  await page.route('**/api/instruments**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 'I1',
          name: 'Test Microscope',
          status: 'available',
          category: 'Microscope',
          description: 'Test microscope for QA',
          location: 'Lab 101',
          image: '/placeholder.svg',
          usageCost: '$20/hr',
        },
      ]),
    });
  });

  await page.goto('/');

  await expect(page.locator('text=Browse Instruments')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Test Microscope' })).toBeVisible({ timeout: 15000 });

  // Directly navigate to details page to avoid card click timing
  await page.goto('/instrument/I1');

  await expect(page.getByRole('heading', { name: 'Test Microscope' })).toBeVisible({ timeout: 10000 });

  // On details page set dates directly in inputs
  const fromInput = page.locator('input[placeholder="YYYY-MM-DD"]').nth(0);
  const toInput = page.locator('input[placeholder="YYYY-MM-DD"]').nth(1);
  // Ensure book now button is present
  await expect(page.locator('text=Add to Booking Bag')).toBeVisible();

  // Fill date fields (today and tomorrow)
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(today.getDate() + 1);

  const d1 = today.toISOString().split('T')[0];
  const d2 = tomorrow.toISOString().split('T')[0];

  await fromInput.fill(d1);
  await toInput.fill(d2);

  // Add to bag and go booking form
  await page.locator('button:has-text("Add to Booking Bag")').click();
  await page.locator('button:has-text("Book Now")').click();

  await page.locator('a:has-text("Proceed to Booking")').click();
  await expect(page.getByRole('heading', { name: 'Booking Request' })).toBeVisible();

  // check email required validation
  await page.fill('input#name', 'Test User');
  await page.fill('input#email', 'invalid-email');
  await page.fill('input#enrollment', 'ENR12345');
  await page.fill('input#dept', 'QA Department');

  await page.locator('button:has-text("Submit Booking Request")').click();

  // The form should not navigate away on invalid email and should stay on Booking Form.
  await expect(page).toHaveURL(/.*booking-form/);
});