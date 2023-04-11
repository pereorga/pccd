import { test, expect } from "@playwright/test";

test.describe("Search", () => {
    test("wildcard search returns all results", async ({ page }) => {
        await page.goto("/");
        const footerText = await page.locator("#contingut > footer p").first().textContent();
        const nParemiotipus = Number.parseInt(
            footerText
                .match(/([\d.])+ paremiotipus/)[0]
                .replace(" paremiotipus", "")
                .replace(".", "")
                .trim(),
            10
        );

        await page.goto("/?mode=&cerca=*&variant=&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const nResultats = resultats
            .match(/trobat ([\d.])+ paremiotipus per a/)[0]
            .replace("trobat ", "")
            .replace(" paremiotipus per a", "")
            .replace(".", "")
            .trim();

        expect(Number.parseInt(nResultats, 10)).toBe(nParemiotipus);
    });

    test("search for 'fera' returns 6 results", async ({ page }) => {
        const expected = 6;
        await page.goto("/?mode=&cerca=fera&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const nResultats = resultats
            .match(/trobat ([\d.])+ paremiotipus per a/)[0]
            .replace("trobat ", "")
            .replace(" paremiotipus per a", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nResultats, 10)).toBe(expected);
    });

    test("search for 'fera' with variants returns more than 20 results", async ({ page }) => {
        const minResults = 20;
        await page.goto("/?mode=&cerca=fera&variant&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const nResultats = resultats
            .match(/trobat ([\d.])+ paremiotipus per a/)[0]
            .replace("trobat ", "")
            .replace(" paremiotipus per a", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nResultats, 10)).toBeGreaterThan(minResults);
    });

    test("search for 'fera' with variants returns less than 30 results", async ({ page }) => {
        const maxResults = 30;
        await page.goto("/?mode=&cerca=fera&variant&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const nResultats = resultats
            .match(/trobat ([\d.])+ paremiotipus per a/)[0]
            .replace("trobat ", "")
            .replace(" paremiotipus per a", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nResultats, 10)).toBeLessThan(maxResults);
    });

    test("search for 'Val més un boig conegut que un savi per conèixer' returns exactly 1 result", async ({ page }) => {
        await page.goto("/?mode=&cerca=Val+m%C3%A9s+un+boig+conegut+que+un+savi+per+con%C3%A8ixer&variant=&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const nResultats = resultats
            .match(/trobat ([\d.])+ paremiotipus per a/)[0]
            .replace("trobat ", "")
            .replace(" paremiotipus per a", "")
            .replace(".", "")
            .trim();
        expect(Number.parseInt(nResultats, 10)).toBe(1);
    });

    test("search for 'asdfasdf' returns no results", async ({ page }) => {
        await page.goto("/?mode=&cerca=asdfasdf&variant=&mostra=10");
        const resultats = await page.locator("#search-form > p").first().textContent();
        const noResultats = resultats.match(/cap resultat coincident/);
        expect(noResultats).toBeTruthy();
    });
});
