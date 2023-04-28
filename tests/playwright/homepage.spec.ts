import { test, expect } from "@playwright/test";
import * as fs from "node:fs";
import * as path from "node:path";

const data = JSON.parse(fs.readFileSync(path.resolve(__dirname, "data/data.json"), "utf8"));

test.describe("Homepage", () => {
    let extractedNumber = "";
    let updatedDate = "";
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 720 });
        await page.goto("/");
    });

    test("has correct title", async ({ page }) => {
        await expect(page).toHaveTitle(data.homepageTitle);
    });

    test("has correct projecte link", async ({ page }) => {
        await page.getByRole("link", { name: "Projecte" }).click();
        await expect(page).toHaveURL(/projecte/);
    });

    test("pager displays 10 rows by default", async ({ page }) => {
        const rows = await page.locator("#search-form tr td").count();
        expect(rows).toBe(10);
    });

    test(`first record is "${data.homepageFirstParemiotipus}"`, async ({ page }) => {
        const firstRecord = await page.locator("#search-form tr td").first().textContent();
        expect(firstRecord).toBe(data.homepageFirstParemiotipus);
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
        const footerText = await page.locator("body > footer p").first().textContent();

        [, updatedDate] = /actualització: (.+)/.exec(footerText);
        expect(updatedDate.trim().length).toBeGreaterThanOrEqual(10);
    });

    test("last updated date has month written properly in catalan", async ({ page }) => {
        const footerText = await page.locator("body > footer p").first().textContent();

        [, updatedDate] = /actualització: (.+)/.exec(footerText);
        expect(updatedDate.trim()).toMatch(
            /(gener|febrer|març|abril|maig|juny|juliol|agost|setembre|octubre|novembre|desembre)/
        );
    });

    test(`has ${data.paremiotipusNumber} paremiotipus`, async ({ page }) => {
        const footerText = await page.locator("body > footer p").first().textContent();

        [, extractedNumber] = /([\d.]+) paremiotipus/.exec(footerText);
        const nParemiotipus = Number(extractedNumber.replace(".", ""));

        expect(nParemiotipus).toBe(data.paremiotipusNumber);
    });

    test(`has ${data.fitxesNumber} fitxes`, async ({ page }) => {
        const footerText = await page.locator("body > footer p").first().textContent();

        [, extractedNumber] = /([\d.]+) fitxes/.exec(footerText);
        const nFitxes = Number(extractedNumber.replace(".", ""));

        expect(nFitxes).toBe(data.fitxesNumber);
    });

    test(`has ${data.fontsNumber} fonts`, async ({ page }) => {
        const footerText = await page.locator("body > footer p").first().textContent();

        [, extractedNumber] = /([\d.]+) fonts/.exec(footerText);
        const nFonts = Number(extractedNumber.replace(".", ""));

        expect(nFonts).toBe(data.fontsNumber);
    });
});
