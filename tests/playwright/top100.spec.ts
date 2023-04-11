import { test, expect } from "@playwright/test";

test.describe("Top paremiotipus", () => {
    test("top 100 has 100 records", async ({ page }) => {
        await page.goto("/top100");
        const nRecords = await page.locator("#contingut ol li").count();
        expect(Number.parseInt(nRecords, 10)).toEqual(100);
    });

    test("top 10000 has 10000 records", async ({ page }) => {
        await page.goto("/top10000");
        const nRecords = await page.locator("#contingut ol li").count();
        expect(Number.parseInt(nRecords, 10)).toEqual(10_000);
    });
});
