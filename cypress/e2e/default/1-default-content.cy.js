/// <reference types="cypress" />

describe("Default content for the homepage", () => {
    beforeEach(() => {
        cy.viewport(1280, 720);
        cy.visit("/");
    });

    it("Pager displays 10 rows by default", () => {
        cy.get("#search-form tr td").should("have.length", 10);
    });

    it('The first record is "A Abrera, donen garses per perdius"', () => {
        cy.get("#search-form tr td").first().should("have.text", "A Abrera, donen garses per perdius");
    });

    it('Block of text containing "Ajudeu-nos a millorar" is visible', () => {
        cy.contains("Ajudeu-nos a millorar").should("be.visible");
    });

    it('Block of text containing "Un projecte de:" is visible', () => {
        cy.contains("Un projecte de:").should("be.visible");
    });

    it('Block of text containing "Un projecte de:" is in the view port', () => {
        cy.contains("Un projecte de:").should("be.inViewport");
    });

    it('Block of text containing "Última actualització:" is visible', () => {
        cy.contains("Última actualització:").should("be.visible");
    });

    it('Block of text containing "Última actualització:" is not in the view port', () => {
        cy.contains("Última actualització:").should("not.be.inViewport");
    });
});
