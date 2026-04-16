import { test, expect } from "@playwright/test";
import { execFileSync } from "node:child_process";
import path from "node:path";

const APP_BASE = "http://127.0.0.1:4173/public/frontend";
const API_BASE = "http://127.0.0.1:8001/api";
const backendDir = path.resolve(process.cwd(), "../istem-backend");
const dbPath = path.resolve(backendDir, "database", "e2e.sqlite").replace(/\\/g, "/");

const b64 = (value: string) => Buffer.from(value, "utf8").toString("base64");

const runPhp = (code: string) =>
  execFileSync("php", ["-r", code], {
    cwd: backendDir,
    encoding: "utf8",
  }).trim();

const getLatestOtp = (email: string) =>
  runPhp(`
    $db = new PDO('sqlite:${dbPath}');
    $email = base64_decode('${b64(email)}');
    $stmt = $db->prepare('select otp from users where email = ? order by id desc limit 1');
    $stmt->execute([$email]);
    echo $stmt->fetchColumn() ?: '';
  `);

const getUserBookingCount = (email: string) =>
  Number(
    runPhp(`
      $db = new PDO('sqlite:${dbPath}');
      $email = base64_decode('${b64(email)}');
      $stmt = $db->prepare('select count(*) from bookings where email = ? or user_email = ?');
      $stmt->execute([$email, $email]);
      echo $stmt->fetchColumn() ?: '0';
    `),
  );

const seedVerifiedUser = (name: string, email: string, password: string, phone: string) => {
  runPhp(`
    $db = new PDO('sqlite:${dbPath}');
    $name = base64_decode('${b64(name)}');
    $email = base64_decode('${b64(email)}');
    $password = base64_decode('${b64(password)}');
    $phone = base64_decode('${b64(phone)}');
    $stmt = $db->prepare('insert into users (name, email, phone, password, email_verified, api_token, otp, otp_expires_at, created_at, updated_at) values (?, ?, ?, ?, 1, null, null, null, datetime("now"), datetime("now"))');
    $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_BCRYPT)]);
  `);
};

test.describe("live end-to-end flows", () => {
  let instrumentId = "";
  let instrumentName = "";

  test.beforeAll(async ({ request }) => {
    instrumentName = `E2E Instrument ${Date.now()}`;

    const response = await request.post(`${API_BASE}/instruments`, {
      data: {
        name: instrumentName,
        category: "Microscope",
        description: "Instrument created for live E2E verification",
        location: "QA Lab",
        usage_cost: "100/hour",
        status: "available",
      },
    });

    expect(response.ok()).toBeTruthy();

    const body = await response.json();
    instrumentId = body?.data?.id;

    expect(instrumentId).toBeTruthy();
  });

  test("user signup -> otp verify -> login -> booking -> my bookings", async ({ page }) => {
    test.setTimeout(180000);

    const suffix = Date.now();
    const name = `E2E User ${suffix}`;
    const email = `e2e.user.${suffix}@example.com`;
    const password = "Password123!";

    await page.goto(`${APP_BASE}/signup`);

    await page.fill("#name", name);
    await page.fill("#email", email);
    await page.fill("#phone", "9876543210");
    await page.fill("#password", password);
    await page.fill("#confirmPassword", password);
    await page.getByRole("button", { name: "Sign up" }).click();

    await expect(page.getByRole("heading", { name: "Verify Email" })).toBeVisible();
    await expect(page).toHaveURL(/\/public\/frontend\/verify-otp\?email=/);

    await expect
      .poll(() => getLatestOtp(email), { timeout: 30000, intervals: [500, 1000, 2000] })
      .toMatch(/^\d{6}$/);

    const otp = getLatestOtp(email);

    await page.fill("#otp", otp);
    await page.getByRole("button", { name: "Verify" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/`);
    await expect(page.getByRole("heading", { name: /Precision Instrumentation/i })).toBeVisible();

    await page.evaluate(() => {
      window.localStorage.clear();
      window.sessionStorage.clear();
    });
    await page.context().clearCookies();

    await page.goto(`${APP_BASE}/login`);
    await expect(page.getByRole("heading", { name: "Login" })).toBeVisible();

    await page.fill("#email", email);
    await page.fill("#password", password);
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/`);
    await expect(page.getByRole("heading", { name: /Precision Instrumentation/i })).toBeVisible();

    await page.goto(`${APP_BASE}/instrument/${instrumentId}`);
    await expect(page.getByRole("heading", { name: instrumentName })).toBeVisible();

    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    const dayAfter = new Date(today);
    dayAfter.setDate(today.getDate() + 2);

    const from = tomorrow.toISOString().split("T")[0];
    const to = dayAfter.toISOString().split("T")[0];

    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(0).fill(from);
    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(1).fill(to);

    await expect(page.getByRole("button", { name: "Add to Booking Bag" })).toBeVisible();
    await page.getByRole("button", { name: "Add to Booking Bag" }).click();
    await page.getByRole("button", { name: "Bag & Review" }).click();

    await expect(page.getByRole("heading", { name: "Booking Bag" })).toBeVisible();
    await page.getByRole("link", { name: /Proceed to Booking/i }).click();

    await expect(page.getByRole("heading", { name: "Booking Request" })).toBeVisible();

    await page.fill("#name", name);
    await page.fill("#email", email);
    await page.fill("#enrollment", `ENR-${suffix}`);
    await page.fill("#dept", "Quality Assurance");
    await page.fill("#project", "Live E2E Booking");
    await page.getByRole("button", { name: "Submit Booking Request" }).click();

    await expect(page.getByRole("heading", { name: "Booking Request Submitted" })).toBeVisible();
    await expect(page.locator("text=Reference ID")).toBeVisible();

    await page.goto(`${APP_BASE}/my-bookings`);
    await expect(page.getByRole("heading", { name: "My Bookings" })).toBeVisible();
    await expect(page.locator(`text=${instrumentId}`)).toBeVisible();

    await expect
      .poll(() => getUserBookingCount(email), { timeout: 15000, intervals: [500, 1000, 2000] })
      .toBeGreaterThan(0);
  });

  test("verified user login -> booking -> my bookings", async ({ page }) => {
    test.setTimeout(180000);

    const suffix = Date.now();
    const name = `Seeded User ${suffix}`;
    const email = `seeded.user.${suffix}@example.com`;
    const password = "Password123!";

    seedVerifiedUser(name, email, password, "9999999999");

    await page.goto(`${APP_BASE}/login`);
    await expect(page.getByRole("heading", { name: "Login" })).toBeVisible();

    await page.fill("#email", email);
    await page.fill("#password", password);
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/`);
    await expect(page.getByRole("heading", { name: /Precision Instrumentation/i })).toBeVisible();

    await page.goto(`${APP_BASE}/instrument/${instrumentId}`);
    await expect(page.getByRole("heading", { name: instrumentName })).toBeVisible();

    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 3);
    const dayAfter = new Date(today);
    dayAfter.setDate(today.getDate() + 4);

    const from = tomorrow.toISOString().split("T")[0];
    const to = dayAfter.toISOString().split("T")[0];

    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(0).fill(from);
    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(1).fill(to);

    await expect(page.getByRole("button", { name: "Add to Booking Bag" })).toBeVisible();
    await page.getByRole("button", { name: "Add to Booking Bag" }).click();
    await page.getByRole("button", { name: "Bag & Review" }).click();

    await expect(page.getByRole("heading", { name: "Booking Bag" })).toBeVisible();
    await page.getByRole("link", { name: /Proceed to Booking/i }).click();

    await expect(page.getByRole("heading", { name: "Booking Request" })).toBeVisible();

    await page.fill("#name", name);
    await page.fill("#email", email);
    await page.fill("#enrollment", `ENR-${suffix}`);
    await page.fill("#dept", "Quality Assurance");
    await page.fill("#project", "Seeded User Booking");
    await page.getByRole("button", { name: "Submit Booking Request" }).click();

    await expect(page.getByRole("heading", { name: "Booking Request Submitted" })).toBeVisible();

    await page.goto(`${APP_BASE}/my-bookings`);
    await expect(page.getByRole("heading", { name: "My Bookings" })).toBeVisible();
    await expect(page.locator(`text=${instrumentId}`)).toBeVisible();

    await expect
      .poll(() => getUserBookingCount(email), { timeout: 15000, intervals: [500, 1000, 2000] })
      .toBeGreaterThan(0);
  });

  test("admin signup -> admin login", async ({ page }) => {
    test.setTimeout(120000);

    const username = `admin_${Date.now()}`;
    const password = "AdminPass123!";

    await page.goto(`${APP_BASE}/admin/signup`);
    await expect(page.getByRole("heading", { name: "Admin Signup" })).toBeVisible();

    await page.fill("#username", username);
    await page.fill("#password", password);
    await page.getByRole("button", { name: "Create Admin Account" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/admin`);
    await expect(page.getByRole("heading", { name: "Admin Dashboard" })).toBeVisible();

    await page.evaluate(() => {
      window.localStorage.clear();
      window.sessionStorage.clear();
    });
    await page.context().clearCookies();

    await page.goto(`${APP_BASE}/admin/login`);
    await expect(page.getByRole("heading", { name: "Admin Login" })).toBeVisible();

    await page.fill("#username", username);
    await page.fill("#password", password);
    await page.getByRole("button", { name: "Login as Admin" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/admin`);
    await expect(page.getByRole("heading", { name: "Admin Dashboard" })).toBeVisible();
    await expect(page.locator("text=Total Instruments")).toBeVisible();
  });
});
