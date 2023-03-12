/// <reference types="cypress" />

describe("Cookie disclaimer", () => {
    beforeEach(() => {
        cy.viewport(1280, 720);
        cy.visit("/");
    });

    const cookieMessage = "Aquest lloc web fa servir galetes de Google per analitzar el trànsit.";

    it("Cookie message is visible", () => {
        cy.contains(cookieMessage).should("be.visible");
    });

    it("Cookie message is in the view port", () => {
        cy.contains(cookieMessage).should("be.inViewport");
    });

    it("Clicking the cookie accept button removes the message, and the message is not visible after reloading", () => {
        cy.get("#snackbar-action").click();
        cy.contains(cookieMessage).should("not.exist");

        cy.reload();
        cy.contains(cookieMessage).should("not.be.visible");
    });
});
