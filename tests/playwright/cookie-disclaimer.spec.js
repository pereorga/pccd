const { test, expect } = require("@playwright/test");
const fs = require("node:fs");
const path = require("node:path");

const data = JSON.parse(fs.readFileSync(path.resolve(__dirname, "data/data.json"), "utf8"));

test.describe("Cookie disclaimer", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });
        await page.goto("/");
    });

    test("message is visible", async ({ page }) => {
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeVisible();
    });

    test("message is in the view port", async ({ page }) => {
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeInViewport();
    });

    test("clicking accept button removes the message, and the message is not visible after reloading", async ({
        page,
    }) => {
        await page.click("#cookie-banner button");
        // For that case, both of these work. The first one is more precise, but the second could be more reliable.
        await expect(page.locator(`text=${data.cookieMessage}`)).toHaveCount(0);
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeHidden();

        // Reload the page and check that the message is not visible.
        await page.reload();
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeHidden();
    });
});
