import { test, expect } from "@playwright/test";

test.describe("Paremiotipus", () => {
    test(`"Qui no vulgui pols, que no vagi a l'era" has at least 550 records`, async ({ page }) => {
        const minRecurrencies = 550;
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const resultats = await page.locator("#contingut .resum").first().textContent();
        const nRecurrencies = resultats
            .match(/([\d.])+\srecurrències/)[0]
            .replace("recurrències", "")
            .replace("&nbsp;", "")
            .trim();
        expect(Number.parseInt(nRecurrencies, 10)).toBeGreaterThanOrEqual(minRecurrencies);
    });

    test(`"Qui no vulgui pols, que no vagi a l'era" has at least 120 variants`, async ({ page }) => {
        const minVariants = 120;
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const resultats = await page.locator("#contingut .resum").first().textContent();
        const nVariants = resultats
            .match(/en ([\d.])+\svariants/)[0]
            .replace("en ", "")
            .replace("variants", "")
            .replace("&nbsp;", "")
            .trim();
        expect(Number.parseInt(nVariants, 10)).toBeGreaterThanOrEqual(minVariants);
    });

    test(`"Val més un boig conegut que un savi per conèixer" has CV audio`, async ({ page }) => {
        await page.goto("/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer");
        const commonVoicePath = await page.locator("#commonvoice audio").first().getAttribute("src");
        expect(commonVoicePath).toContain(".mp3");
    });
});
