import { test, expect } from "@playwright/test";

test.describe("Cookie disclaimer", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });
        await page.goto("/");
    });

    const cookieMessage = "Aquest lloc web fa servir galetes de Google per analitzar el trànsit.";

    test("message is visible", async ({ page }) => {
        await expect(page.locator(`text=${cookieMessage}`)).toBeVisible();
    });

    test("message is in the view port", async ({ page }) => {
        await expect(page.locator(`text=${cookieMessage}`)).toBeInViewport();
    });

    test("clicking accept button removes the message, and the message is not visible after reloading", async ({
        page,
    }) => {
        await page.click("#snackbar-action");
        // For that case, both of these work. The first one is more precise, but the second could be more reliable.
        await expect(page.locator(`text=${cookieMessage}`)).toHaveCount(0);
        await expect(page.locator(`text=${cookieMessage}`)).toBeHidden();

        // Reload the page and check that the message is not visible.
        await page.reload();
        await expect(page.locator(`text=${cookieMessage}`)).toBeHidden();
    });
});
