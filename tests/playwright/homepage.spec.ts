import { test, expect } from "@playwright/test";

test.describe("Homepage", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });
        await page.goto("/");
    });

    test("has correct title", async ({ page }) => {
        await expect(page).toHaveTitle(/Paremiologia catalana comparada digital - PCCD/);
    });

    test("has correct projecte link", async ({ page }) => {
        await page.getByRole("link", { name: "Projecte" }).click();
        await expect(page).toHaveURL(/projecte/);
    });

    test("pager displays 10 rows by default", async ({ page }) => {
        const rows = await page.locator("#search-form tr td").count();
        expect(rows).toBe(10);
    });

    test('first record is "A Abrera, donen garses per perdius"', async ({ page }) => {
        const firstRecord = await page.locator("#search-form tr td").first().textContent();
        expect(firstRecord).toBe("A Abrera, donen garses per perdius");
    });

    test('block of text containing "Ajudeu-nos a millorar" is visible', async ({ page }) => {
        await expect(page.locator("text=Ajudeu-nos a millorar")).toBeVisible();
    });

    test('block of text containing "Un projecte de:" is visible', async ({ page }) => {
        await expect(page.locator("text=Un projecte de:")).toBeVisible();
    });

    test('block of text containing "Un projecte de:" is in the view port', async ({ page }) => {
        await expect(page.locator("text=Un projecte de:")).toBeInViewport();
    });

    test('block of text containing "Última actualització" is visible', async ({ page }) => {
        await expect(page.locator("text=Última actualització")).toBeVisible();
    });

    test('block of text containing "Última actualització" is not in the view port', async ({ page }) => {
        await expect(page.locator("text=Última actualització:")).not.toBeInViewport();
    });

    test("has last updated date set", async ({ page }) => {
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const data = footerText
            .match(/actualització: (.)+/)[0]
            .replace("actualització:", "")
            .trim();
        expect(data.length).toBeGreaterThanOrEqual(10);
    });

    test("last updated date has month written properly in catalan", async ({ page }) => {
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const data = footerText
            .match(/actualització: (.)+/)[0]
            .replace("actualització:", "")
            .trim();
        expect(data).toMatch(/(gener|febrer|març|abril|maig|juny|juliol|agost|setembre|octubre|novembre|desembre)/);
    });

    test("has more than N paremiotipus", async ({ page }) => {
        const minParemiotipus = 54_000;
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const nParemiotipus = Number.parseInt(
            footerText
                .match(/([\d.])+ paremiotipus/)[0]
                .replace(" paremiotipus", "")
                .replace(".", "")
                .trim(),
            10
        );
        expect(nParemiotipus).toBeGreaterThan(minParemiotipus);
    });

    test("has more than N fitxes", async ({ page }) => {
        const minFitxes = 521_000;
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const nFitxes = Number.parseInt(
            footerText
                .match(/[\d.]+/)[0]
                .replace(".", "")
                .trim(),
            10
        );
        expect(nFitxes).toBeGreaterThan(minFitxes);
    });

    test("has more than N fonts", async ({ page }) => {
        const minFonts = 5000;
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const nFonts = Number.parseInt(
            footerText
                .match(/([\d.])+ fonts/)[0]
                .replace(" fonts", "")
                .replace(".", "")
                .trim(),
            10
        );
        expect(nFonts).toBeGreaterThan(minFonts);
    });
});
