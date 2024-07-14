/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

(() => {
    const toggleAllSources = () => {
        const toggleAllButton = document.querySelector("#toggle-all");
        const isExpanded = toggleAllButton.textContent.startsWith("Contrau");
        toggleAllButton.textContent = isExpanded ? "Desplega-ho tot" : "Contrau-ho tot";
        toggleAllButton.title = (isExpanded ? "Mostra" : "Amaga") + " els detalls de cada font";
        for (const element of document.querySelectorAll("details")) {
            element.toggleAttribute("open", !isExpanded);
        }
    };

    const toggleAllButton = document.querySelector("#toggle-all");
    if (toggleAllButton) {
        // Collapse all sources if this is the user's preference.
        if (localStorage.getItem("always_expand") === "2") {
            toggleAllSources();
        }
        toggleAllButton.addEventListener("click", (event) => {
            toggleAllSources();
            localStorage.setItem("always_expand", event.target.textContent.startsWith("Desplega") ? "2" : "1");
        });
    }

    // Play Common Voice files on click.
    for (const audio of document.querySelectorAll(".audio")) {
        audio.addEventListener("click", (event) => {
            event.preventDefault();
            audio.firstElementChild.play();
        });
    }

    // Make elements with role="button" to behave consistently with buttons.
    for (const button of document.querySelectorAll('[role="button"]')) {
        button.addEventListener("keydown", (event) => {
            if (event.key === " ") {
                event.preventDefault();
                button.click();
            }
        });
    }
})();
