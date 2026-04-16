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

const seedVerifiedUser = (name: string, email: string, password: string) => {
  runPhp(`
    $db = new PDO('sqlite:${dbPath}');
    $name = base64_decode('${b64(name)}');
    $email = base64_decode('${b64(email)}');
    $password = base64_decode('${b64(password)}');
    $stmt = $db->prepare('insert into users (name, email, phone, password, email_verified, api_token, otp, otp_expires_at, created_at, updated_at) values (?, ?, ?, ?, 1, null, null, null, datetime("now"), datetime("now"))');
    $stmt->execute([$name, $email, '9999999999', password_hash($password, PASSWORD_BCRYPT)]);
  `);
};

test.describe("booking diagnostic", () => {
  let instrumentId = "";
  let instrumentName = "";
  const consoleLogs: string[] = [];

  test.beforeAll(async ({ request }) => {
    instrumentName = `Diagnostic Instrument ${Date.now()}`;

    const response = await request.post(`${API_BASE}/instruments`, {
      data: {
        name: instrumentName,
        category: "Microscope",
        description: "Diagnostic instrument",
        location: "Lab",
        usage_cost: "100/hour",
        status: "available",
      },
    });

    const body = await response.json();
    instrumentId = body?.data?.id;
  });

  test("verified user booking submit diagnostic", async ({ page }) => {
    test.setTimeout(120000);

    // Capture all console messages
    page.on("console", (msg) => {
      const text = `${msg.type()}: ${msg.text()}`;
      consoleLogs.push(text);
      console.log(text);
    });

    // Seed a verified user
    const suffix = Date.now();
    const name = `Diagnostic User ${suffix}`;
    const email = `diagnostic.user.${suffix}@example.com`;
    const password = "Password123!";

    seedVerifiedUser(name, email, password);

    // Login
    await page.goto(`${APP_BASE}/login`);
    await page.fill("#email", email);
    await page.fill("#password", password);
    await page.getByRole("button", { name: "Login" }).click();

    await expect(page).toHaveURL(`${APP_BASE}/`);
    console.log("✅ Login successful");

    // Go to instrument
    await page.goto(`${APP_BASE}/instrument/${instrumentId}`);
    await expect(page.getByRole("heading", { name: instrumentName })).toBeVisible();
    console.log("✅ Instrument page loaded");

    // Select dates
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 5);
    const dayAfter = new Date(today);
    dayAfter.setDate(today.getDate() + 6);

    const from = tomorrow.toISOString().split("T")[0];
    const to = dayAfter.toISOString().split("T")[0];

    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(0).fill(from);
    await page.locator('input[placeholder="YYYY-MM-DD"]').nth(1).fill(to);

    // Add to bag
    await page.getByRole("button", { name: "Add to Booking Bag" }).click();
    await page.getByRole("button", { name: "Bag & Review" }).click();

    await expect(page.getByRole("heading", { name: "Booking Bag" })).toBeVisible();
    await page.getByRole("link", { name: /Proceed to Booking/i }).click();

    // Fill booking form
    await expect(page.getByRole("heading", { name: "Booking Request" })).toBeVisible();
    const userNameInput = page.getByLabel("Student Name *");
    await userNameInput.fill(name);
    
    const emailInput = page.getByLabel("Email Address *");
    await emailInput.fill(email);
    
    const enrollmentInput = page.getByLabel("Enrollment Number *");
    await enrollmentInput.fill(`ENR-${suffix}`);
    
    const deptInput = page.getByLabel("Department *");
    await deptInput.fill("Test Dept");
    
    // Select a program
    const programDropdown = page.locator('select').filter({ has: page.locator('option:has-text("B.Tech")') }).first();
    if (await programDropdown.isVisible()) {
      await programDropdown.selectOption("B.Tech");
      console.log("✅ Program selected");
    } else {
      console.log("⚠️ Program dropdown not found or interactive");
    }

    const projectInput = page.getByLabel("Project Title");
    await projectInput.fill("Diagnostic Test");

    console.log("✅ Form fields filled");
    console.log("📋 Diagnostic logs before submit:");
    consoleLogs.forEach(log => console.log("  " + log));

    // SUBMIT - this is where it failed before
    console.log("🔵 Clicking Submit Booking Request...");
    await page.getByRole("button", { name: "Submit Booking Request" }).click();

    // Wait and capture console output
    await page.waitForTimeout(2000);

    console.log("📋 Diagnostic logs after submit:");
    consoleLogs.forEach(log => console.log("  " + log));

    // Check for error toast or success
    const errorToast = page.locator("text=Something went wrong");
    const successHeading = page.getByRole("heading", { name: "Booking Request Submitted" });

    if (await errorToast.isVisible({ timeout: 5000 })) {
      console.log("❌ Error toast appeared");
      throw new Error("Booking submit failed with error toast");
    }

    if (!await successHeading.isVisible({ timeout: 5000 })) {
      console.log("❌ Success page not reached within timeout");
      console.log("📋 Final console logs:");
      consoleLogs.forEach(log => console.log("  " + log));
      throw new Error("Booking submit did not navigate to confirmation page");
    }

    console.log("✅ Booking submit successful - confirmed page navigation");
  });
});
