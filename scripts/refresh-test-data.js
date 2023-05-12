const fs = require("node:fs");
const { chromium } = require("playwright");
const dotenv = require("dotenv");

dotenv.config();

const dataPath = `${__dirname}/../tests/playwright/data/data.json`;
const data = JSON.parse(fs.readFileSync(dataPath, "utf8"));

const extractNumber = (text, regex) => {
    const [, extractedNumber] = regex.exec(text);
    return Number(extractedNumber.replace(".", ""));
};

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();

    await page.goto(process.env.BASE_URL);

    data.homepageFirstParemiotipus = await page.locator("table td").first().textContent();

    const footerText = await page.locator("body > footer p").first().textContent();
    data.paremiotipusNumber = extractNumber(footerText, /([\d.]+) paremiotipus/);
    data.fitxesNumber = extractNumber(footerText, /([\d.]+) fitxes/);
    data.fontsNumber = extractNumber(footerText, /([\d.]+) fonts/);

    await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&mostra=10`);
    let content = await page.locator("#search-form > p").first().textContent();
    data.searchFeraNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

    await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&variant&mostra=10`);
    content = await page.locator("#search-form > p").first().textContent();
    data.searchFeraWithVariantsNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

    await page.goto(`${process.env.BASE_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`);
    content = await page.locator("main .resum").first().textContent();
    data.paremiotipusQuiNoVulguiPolsNumberOfEntries = extractNumber(content, /([\d.]+)\srecurrències/);
    data.paremiotipusQuiNoVulguiPolsNumberOfVariants = extractNumber(content, /en ([\d.]+)\svariants/);

    await page.goto(
        `${process.env.BASE_URL}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982`
    );
    const obra = await page.locator("article.col-obra").textContent();
    data.obraFolkloreCatalunyaNumberOfEntries = extractNumber(
        obra,
        /Aquesta obra té ([\d.]+) fitxes a la base de dades/
    );
    data.obraFolkloreCatalunyaNumberOfEntriesCollected = extractNumber(obra, /de les quals ([\d.]+) estan recollides/);

    fs.writeFileSync(dataPath, JSON.stringify(data, undefined, 2) + "\n", "utf8");

    await browser.close();
})();
