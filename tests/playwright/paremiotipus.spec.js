const { test, expect } = require("@playwright/test");
const fs = require("node:fs");
const path = require("node:path");

const data = JSON.parse(fs.readFileSync(path.resolve(__dirname, "data/data.json"), "utf8"));

test.describe("Paremiotipus", () => {
    let extractedNumber = "";
    test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfEntries} records`, async ({
        page,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const results = await page.locator(".description").textContent();

        [, extractedNumber] = /([\d.]+)\srecurrències/.exec(results);
        const nEntries = Number(extractedNumber.replace(".", ""));
        expect(nEntries).toBe(data.paremiotipusQuiNoVulguiPolsNumberOfEntries);
    });

    test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfVariants} variants`, async ({
        page,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const results = await page.locator(".description").textContent();

        [, extractedNumber] = /en ([\d.]+)\svariants/.exec(results);
        const nVariants = Number(extractedNumber.replace(".", ""));
        expect(nVariants).toBe(data.paremiotipusQuiNoVulguiPolsNumberOfVariants);
    });

    test(`"Val més un boig conegut que un savi per conèixer" has CV audio`, async ({ page }) => {
        await page.goto("/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer");
        const commonVoicePath = await page.locator("#commonvoice audio").first().getAttribute("src");
        expect(commonVoicePath).toContain(".mp3");
    });
});
