import { test, expect } from "@playwright/test";

test.describe("Top paremiotipus", () => {
    test("top 100 has 100 entries", async ({ page }) => {
        await page.goto("/top100");
        const nRecords = await page.locator("main ol li").count();
        expect(nRecords).toEqual(100);
    });

    test("top 10000 has 10000 entries", async ({ page }) => {
        await page.goto("/top10000");
        const nRecords = await page.locator("main ol li").count();
        expect(nRecords).toEqual(10_000);
    });
});
