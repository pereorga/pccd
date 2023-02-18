#!/usr/bin/env node
/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

/*
 * Functional tests for the PCCD.
 */

"use strict";

const syncFetch = require("sync-fetch");
const cheerio = require("cheerio");
const assert = require("assert");

const PROD_URL = "https://pccd.dites.cat";
let ENVIRONMENT_URL = "http://localhost:8092";
const args = process.argv.slice(2);
if (typeof args[0] !== "undefined") {
    ENVIRONMENT_URL = args[0];
}

{
    console.log("Hi ha una data d'última actualització establerta");
    const $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const data = footerText
        .match(/actualització: (.)+/)[0]
        .replace("actualització:", "")
        .trim();
    assert.ok(data.length > 10);
    console.log("[OK]\n");
}

{
    console.log("La data de l'última actualització inclou el mes (en català i en minúscules)");
    const $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const data = footerText
        .match(/actualització: (.)+/)[0]
        .replace("actualització:", "")
        .trim();
    assert.ok(
        data.includes("gener") ||
            data.includes("febrer") ||
            data.includes("març") ||
            data.includes("abril") ||
            data.includes("maig") ||
            data.includes("juny") ||
            data.includes("juliol") ||
            data.includes("agost") ||
            data.includes("setembre") ||
            data.includes("octubre") ||
            data.includes("novembre") ||
            data.includes("desembre")
    );
    console.log("[OK]\n");
}

{
    const minParemiotipus = 50000;
    console.log(`Hi ha més de ${minParemiotipus} paremiotipus`);
    const $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const nParemiotipus = footerText
        .match(/([0-9.])+ paremiotipus/)[0]
        .replace(" paremiotipus", "")
        .replace(".", "")
        .trim();
    assert.ok(nParemiotipus > minParemiotipus);
    console.log("[OK]\n");
}

{
    const minFitxes = 485000;
    console.log(`Hi ha més de ${minFitxes} fitxes`);
    const $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const nFitxes = footerText
        .match(/[0-9.]+/)[0]
        .replace(".", "")
        .trim();
    assert.ok(nFitxes > minFitxes);
    console.log("[OK]\n");
}

{
    const minFonts = 4800;
    console.log(`Hi ha més de ${minFonts} fonts`);
    const $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const nFonts = footerText
        .match(/([0-9.])+ fonts/)[0]
        .replace(" fonts", "")
        .replace(".", "")
        .trim();
    assert.ok(nFonts > minFonts);
    console.log("[OK]\n");
}

{
    console.log("El nombre de paremiotipus quadra amb el nombre de resultats d'una cerca amb asterisc");
    let $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    const footerText = $("#contingut > footer p").text();
    const nParemiotipus = footerText
        .match(/([0-9.])+ paremiotipus/)[0]
        .replace(" paremiotipus", "")
        .replace(".", "")
        .trim();
    $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/?mode=&cerca=*&variant=&mostra=10`).text());
    const resultats = $("#search-form > p").first().text();
    const nResultats = resultats
        .match(/trobat ([0-9.])+ paremiotipus per a/)[0]
        .replace("trobat ", "")
        .replace(" paremiotipus per a", "")
        .replace(".", "")
        .trim();
    assert.equal(nParemiotipus, nResultats);
    console.log("[OK]\n");
}

{
    const exactResults = 6;
    console.log(`La cerca 'fera' sense variants retorna exactament ${exactResults} resultats`);
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/?mode=&cerca=fera&mostra=10`).text());
    const resultats = $("#search-form > p").first().text();
    const nResultats = resultats
        .match(/trobat ([0-9.])+ paremiotipus per a/)[0]
        .replace("trobat ", "")
        .replace(" paremiotipus per a", "")
        .replace(".", "")
        .trim();
    assert.equal(nResultats, exactResults);
    console.log("[OK]\n");
}

{
    const minResults = 20;
    console.log(`La cerca 'fera' amb variants retorna més ${minResults} resultats`);
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/?mode=&cerca=fera&variant&mostra=10`).text());
    const resultats = $("#search-form > p").first().text();
    const nResultats = resultats
        .match(/trobat ([0-9.])+ paremiotipus per a/)[0]
        .replace("trobat ", "")
        .replace(" paremiotipus per a", "")
        .replace(".", "")
        .trim();
    assert.ok(nResultats > minResults);
    console.log("[OK]\n");
}

{
    const maxResults = 30;
    console.log(`La cerca 'fera' amb variants retorna menys de ${maxResults} resultats`);
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/?mode=&cerca=fera&variant&mostra=10`).text());
    const resultats = $("#search-form > p").first().text();
    const nResultats = resultats
        .match(/trobat ([0-9.])+ paremiotipus per a/)[0]
        .replace("trobat ", "")
        .replace(" paremiotipus per a", "")
        .replace(".", "")
        .trim();
    assert.ok(nResultats < maxResults);
    console.log("[OK]\n");
}

{
    console.log("La cerca 'Val més un boig conegut que un savi per conèixer' retorna exactament 1 resultat");
    const $ = cheerio.load(
        syncFetch(
            `${ENVIRONMENT_URL}/?mode=&cerca=Val+m%C3%A9s+un+boig+conegut+que+un+savi+per+con%C3%A8ixer&variant=&mostra=10`
        ).text()
    );
    const resultats = $("#search-form > p").first().text();
    const nResultats = resultats
        .match(/trobat ([0-9.])+ paremiotipus per a/)[0]
        .replace("trobat ", "")
        .replace(" paremiotipus per a", "")
        .replace(".", "")
        .trim();
    assert.equal(nResultats, 1);
    console.log("[OK]\n");
}

{
    console.log("La cerca 'asdfasdf' no retorna res");
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/?mode=&cerca=asdfasdf&variant=&mostra=10`).text());
    const resultats = $("#search-form > p").first().text();
    const noResultats = resultats.match(/cap resultat coincident/);
    assert.ok(Object.prototype.toString.call(noResultats) === "[object Array]");
    console.log("[OK]\n");
}

{
    const minRecurrencies = 550;
    console.log(`'Qui no vulgui pols, que no vagi a l'era' té almenys ${minRecurrencies} recurrències`);
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`).text());
    const resultats = $("#contingut .resum").first().text();
    const nRecurrencies = resultats
        .match(/([0-9.])+ recurrències/)[0]
        .replace(" recurrències", "")
        .trim();
    assert.ok(nRecurrencies >= minRecurrencies);
    console.log("[OK]\n");
}

{
    const minVariants = 120;
    console.log(`'Qui no vulgui pols, que no vagi a l'era' té almenys ${minVariants} variants`);
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`).text());
    const resultats = $("#contingut .resum").first().text();
    const nVariants = resultats
        .match(/en ([0-9.])+ variants/)[0]
        .replace("en ", "")
        .replace(" variants", "")
        .trim();
    assert.ok(nVariants >= minVariants);
    console.log("[OK]\n");
}

{
    console.log("'Val més un boig conegut que un savi per conèixer' té fitxers de Common Voice en format MP3");
    const $ = cheerio.load(
        syncFetch(`${ENVIRONMENT_URL}/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer`).text()
    );
    const commonVoicePath = $("#commonvoice audio").first().attr("src");
    assert.ok(commonVoicePath.includes(".mp3"));
    console.log("[OK]\n");
}

{
    console.log("La pàgina top100 llista exactament 100 paremiotipus");
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/top100`).text());
    const nParemiotipus = $("#contingut ol li").length;
    assert.equal(nParemiotipus, 100);
    console.log("[OK]\n");
}

{
    console.log("La pàgina top10000 llista exactament 10000 paremiotipus");
    const $ = cheerio.load(syncFetch(`${ENVIRONMENT_URL}/top10000`).text());
    const nParemiotipus = $("#contingut ol li").length;
    assert.equal(nParemiotipus, 10000);
    console.log("[OK]\n");
}

{
    const minFitxes = 21000;
    const minRecollides = 18000;
    console.log(
        `L'obra 'Folklore de Catalunya. Cançoner' té més de ${minFitxes} fitxes, de les quals més de ${minRecollides} recollides`
    );
    const $ = cheerio.load(
        syncFetch(
            `${ENVIRONMENT_URL}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982`
        ).text()
    );
    const obra = $("#contingut article.obra").text();
    const nFitxes = obra
        .match(/Aquesta obra té ([0-9.])+ fitxes a la base de dades/)[0]
        .replace("Aquesta obra té ", "")
        .replace(" fitxes a la base de dades", "")
        .replace(".", "")
        .trim();
    const nRecollides = obra
        .match(/de les quals ([0-9.])+ estan recollides/)[0]
        .replace("de les quals ", "")
        .replace(" estan recollides", "")
        .replace(".", "")
        .trim();
    assert.ok(nFitxes >= minFitxes);
    assert.ok(nRecollides >= minRecollides);
    console.log("[OK]\n");
}

{
    console.log("El nombre de paremiotipus és consistent amb producció");
    let $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    let footerText = $("#contingut > footer p").text();
    const nParemiotipus = footerText
        .match(/([0-9.])+ paremiotipus/)[0]
        .replace(" paremiotipus", "")
        .replace(".", "")
        .trim();
    $ = cheerio.load(syncFetch(PROD_URL).text());
    footerText = $("#contingut > footer p").text();
    const nParemiotipusProd = footerText
        .match(/([0-9.])+ paremiotipus/)[0]
        .replace(" paremiotipus", "")
        .replace(".", "")
        .trim();
    if (nParemiotipus === nParemiotipusProd) {
        console.log("[INFO] El nombre coincideix amb producció.");
    } else if (nParemiotipus > nParemiotipusProd) {
        console.log("[INFO] El nombre és més gran que a producció.");
    }
    assert.ok(nParemiotipus >= nParemiotipusProd);
    console.log("[OK]\n");
}

{
    console.log("El nombre de parèmies és consistent amb producció");
    let $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    let footerText = $("#contingut > footer p").text();
    const nFitxes = footerText
        .match(/[0-9.]+/)[0]
        .replace(".", "")
        .trim();
    $ = cheerio.load(syncFetch(PROD_URL).text());
    footerText = $("#contingut > footer p").text();
    const nFitxesProd = footerText
        .match(/[0-9.]+/)[0]
        .replace(".", "")
        .trim();
    assert.ok(nFitxes >= nFitxesProd);
    if (nFitxes === nFitxesProd) {
        console.log("[INFO] El nombre coincideix amb producció.");
    } else if (nFitxes > nFitxesProd) {
        console.log("[INFO] El nombre és més gran que a producció.");
    }
    console.log("[OK]\n");
}

{
    console.log("El nombre de fonts és consistent amb producció");
    let $ = cheerio.load(syncFetch(ENVIRONMENT_URL).text());
    let footerText = $("#contingut > footer p").text();
    const nFonts = footerText
        .match(/([0-9.])+ fonts/)[0]
        .replace(" fonts", "")
        .replace(".", "")
        .trim();
    $ = cheerio.load(syncFetch(PROD_URL).text());
    footerText = $("#contingut > footer p").text();
    const nFontsProd = footerText
        .match(/([0-9.])+ fonts/)[0]
        .replace(" fonts", "")
        .replace(".", "")
        .trim();
    assert.ok(nFonts >= nFontsProd);
    if (nFonts === nFontsProd) {
        console.log("[INFO] El nombre coincideix amb producció.");
    } else if (nFonts > nFontsProd) {
        console.log("[INFO] El nombre és més gran que a producció.");
    }
    console.log("[OK]\n");
}
