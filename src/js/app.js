/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

// Google Tag Manager code.
window.dataLayer = window.dataLayer || [];
const gtag = function () {
    // eslint-disable-next-line no-undef
    dataLayer.push(arguments);
};
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");

(function () {
    "use strict";

    const toggleAllSources = function () {
        const toggleAllElement = document.querySelector("#toggle-all");
        const details = document.querySelectorAll("details");

        // If it is not expanded, expand it.
        if (toggleAllElement.textContent.includes("desplega")) {
            for (const element of details) {
                element.setAttribute("open", "true");
            }
            toggleAllElement.textContent = "contrau-ho tot";
            toggleAllElement.setAttribute("title", "Amaga els detalls de cada font");
        } else {
            // Otherwise collapse it.
            for (const element of details) {
                element.removeAttribute("open");
            }
            toggleAllElement.textContent = "desplega-ho tot";
            toggleAllElement.setAttribute("title", "Mostra els detalls de cada font");
        }
    };

    // Keyboard shortcut to go the homepage.
    document.addEventListener("keydown", (event) => {
        if (event.ctrlKey && (event.key === "/" || event.key === "7")) {
            document.location.assign("/");
        }
    });

    const isTouchDevice = "ontouchstart" in window || navigator.maxTouchPoints > 0;
    const searchBox = document.querySelector("#cerca");

    // Search/homepage.
    if (searchBox) {
        const variantCheckbox = document.querySelector("#variant");
        const sinonimCheckbox = document.querySelector("#sinonim");
        const equivalentCheckbox = document.querySelector("#equivalent");

        // Remember the search options, but only if the search is empty (e.g. we are in the homepage).
        if (searchBox.value === "") {
            if (localStorage.getItem("variant") === "2") {
                variantCheckbox.checked = false;
            }
            if (localStorage.getItem("sinonim") === "1") {
                sinonimCheckbox.checked = true;
            }
            if (localStorage.getItem("equivalent") === "1") {
                equivalentCheckbox.checked = true;
            }
        }

        const nextButton = document.querySelector(".page-link[rel=next]");
        const previousButton = document.querySelector(".page-link[rel=prev]");
        const isMac = navigator.userAgent.includes("Macintosh");
        if (nextButton) {
            nextButton.setAttribute("title", isMac ? "Pàgina següent (Ctrl ⇧ →)" : "Pàgina següent (Ctrl →)");
        }
        if (previousButton) {
            previousButton.setAttribute("title", isMac ? "Pàgina anterior (Ctrl ⇧ ←)" : "Pàgina anterior (Ctrl ←)");
        }

        // Search keyboard shortcuts.
        document.addEventListener("keydown", (event) => {
            if (event.ctrlKey) {
                if (event.key === "ArrowRight" && nextButton) {
                    document.location.assign(nextButton.getAttribute("href"));
                } else if (event.key === "ArrowLeft" && previousButton) {
                    document.location.assign(previousButton.getAttribute("href"));
                }
            }
        });

        // Pager is only available when there are more than 10 results.
        const mostra = document.querySelector("#mostra");
        if (mostra) {
            mostra.addEventListener("change", () => {
                document.querySelector("#search-form").submit();
            });
        }

        // Store checkboxes values in local storage.
        variantCheckbox.addEventListener("change", () => {
            localStorage.setItem("variant", variantCheckbox.checked ? "1" : "2");
        });
        sinonimCheckbox.addEventListener("change", () => {
            localStorage.setItem("sinonim", sinonimCheckbox.checked ? "1" : "");
        });
        equivalentCheckbox.addEventListener("change", () => {
            localStorage.setItem("equivalent", equivalentCheckbox.checked ? "1" : "");
        });
    } else {
        // Play Common Voice on-click in paremiotipus pages.
        for (const audio of document.querySelectorAll(".audio")) {
            audio.addEventListener("click", (event) => {
                event.preventDefault();
                audio.firstElementChild.play();

                // Preload the other audio files.
                for (const element of document.querySelectorAll("audio")) {
                    element.setAttribute("preload", "auto");
                }
            });
        }

        // Toggle all sources in paremiotipus pages.
        const toggleAllElement = document.querySelector("#toggle-all");
        if (toggleAllElement) {
            toggleAllElement.addEventListener("click", (event) => {
                const isExpanded = event.target.textContent.includes("contrau");
                toggleAllSources();
                if (isExpanded) {
                    localStorage.setItem("always_expand", "2");
                } else {
                    localStorage.setItem("always_expand", "1");
                }
            });
            // Collapse all sources if this is the user's preference.
            if (localStorage.getItem("always_expand") === "2") {
                toggleAllSources();
            }
        }
    }

    // Ensure the following is executed with browser back/forward navigation.
    if (!isTouchDevice) {
        window.addEventListener("pageshow", () => {
            // In the search page, select the text inside the search box, but not in touch devices.
            if (searchBox && searchBox.value) {
                searchBox.select();
            }
        });
    }

    // Show the cookie alert.
    if (localStorage.getItem("accept_cookies") !== "1") {
        const snackBar = document.querySelector("#snackbar");
        snackBar.classList.remove("d-none");
        snackBar.querySelector("button").addEventListener("click", () => {
            snackBar.remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Toggle hamburger menu.
    document.querySelector("#navbar-toggle").addEventListener("click", () => {
        document.querySelector("#menu").classList.toggle("d-none");
    });

    // Slower devices may benefit of preloading pages (Quicklink already checks if the device connection is too slow).
    if (isTouchDevice) {
        const script = document.createElement("script");
        script.src = "/js/quicklink.umd.min.js";
        script.addEventListener("load", () => {
            // eslint-disable-next-line no-undef
            quicklink.listen();
        });
        document.body.append(script);
    }
})();
