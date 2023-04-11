import { test, expect } from "@playwright/test";

test.describe("Obra", () => {
    test(`"Folklore de Catalunya. Cançoner" has at least X records, of which Y collected`, async ({ page }) => {
        await page.goto("/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982");

        const minFitxes = 21_000;
        const obra = await page.locator("#contingut article.obra").textContent();
        const nFitxes = obra
            .match(/Aquesta obra té ([\d.])+ fitxes a la base de dades/)[0]
            .replace("Aquesta obra té ", "")
            .replace(" fitxes a la base de dades", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nFitxes, 10)).toBeGreaterThan(minFitxes);

        const minRecollides = 18_000;
        const nRecollides = obra
            .match(/de les quals ([\d.])+ estan recollides/)[0]
            .replace("de les quals ", "")
            .replace(" estan recollides", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nRecollides, 10)).toBeGreaterThan(minRecollides);
    });
});
