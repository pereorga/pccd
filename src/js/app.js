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
        const toggle = document.querySelector("#toggle-all");
        const isExpanded = toggle.textContent.includes("contrau");
        for (const element of document.querySelectorAll("details")) {
            if (isExpanded) {
                element.removeAttribute("open");
            } else {
                element.setAttribute("open", "true");
            }
        }
        toggle.textContent = isExpanded ? "desplega-ho tot" : "contrau-ho tot";
        toggle.setAttribute("title", (isExpanded ? "Mostra" : "Amaga") + " els detalls de cada font");
    };

    const isTouchDevice = "ontouchstart" in window;
    const searchBox = document.querySelector("#cerca");
    if (searchBox) {
        // We are in the search page / homepage.
        const variantCheckbox = document.querySelector("#variant");
        const sinonimCheckbox = document.querySelector("#sinonim");
        const equivalentCheckbox = document.querySelector("#equivalent");
        const nextButton = document.querySelector(".page-link[rel=next]");
        const previousButton = document.querySelector(".page-link[rel=prev]");

        // Remember the search options, but only if the search is empty (e.g. we are in the homepage).
        if (searchBox.value === "") {
            variantCheckbox.checked = localStorage.getItem("variant") !== "2";
            sinonimCheckbox.checked = localStorage.getItem("sinonim") === "1";
            equivalentCheckbox.checked = localStorage.getItem("equivalent") === "1";
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

        // Search keyboard shortcuts.
        document.addEventListener("keydown", (event) => {
            if (event.ctrlKey) {
                if (event.key === "ArrowRight" && nextButton) {
                    location.assign(nextButton.getAttribute("href"));
                } else if (event.key === "ArrowLeft" && previousButton) {
                    location.assign(previousButton.getAttribute("href"));
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
    } else {
        // All other pages.
        // Play Common Voice files on click (paremiotipus page).
        for (const audio of document.querySelectorAll(".audio")) {
            audio.addEventListener("click", (event) => {
                event.preventDefault();
                audio.firstElementChild.play();
            });
        }

        // Toggle all sources (paremiotipus page).
        const toggleAllElement = document.querySelector("#toggle-all");
        if (toggleAllElement) {
            toggleAllElement.addEventListener("click", (event) => {
                const isExpanded = event.target.textContent.includes("contrau");
                toggleAllSources();
                localStorage.setItem("always_expand", isExpanded ? "2" : "1");
            });
            // Collapse all sources if this is the user's preference.
            if (localStorage.getItem("always_expand") === "2") {
                toggleAllSources();
            }
        }
    }

    // On non-touch devices.
    if (!isTouchDevice) {
        // Ensure the following is executed with browser back/forward navigation.
        window.addEventListener("pageshow", () => {
            // If we are in the search page and there is text inside the search box, select it.
            if (searchBox && searchBox.value) {
                searchBox.select();
            }
        });
    }

    // Show the cookie alert if it hasn't been accepted.
    if (localStorage.getItem("accept_cookies") !== "1") {
        const snackBar = document.querySelector("#snackbar");
        snackBar.classList.remove("d-none");
        snackBar.querySelector("button").addEventListener("click", () => {
            snackBar.remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Toggle hamburger menu on click.
    document.querySelector("#navbar-toggle").addEventListener("click", () => {
        document.querySelector("#menu").classList.toggle("d-none");
    });

    // Add keyboard shortcut to go the homepage.
    document.addEventListener("keydown", (event) => {
        if (event.ctrlKey && (event.key === "/" || event.key === "7")) {
            location.assign("/");
        }
    });

    // Prefetch internal links on hover/touch.
    for (const a of document.querySelectorAll("a")) {
        if (a.href && a.origin === location.origin) {
            a.addEventListener(isTouchDevice ? "touchstart" : "mouseenter", () => {
                const prefetchLink = document.createElement("link");
                prefetchLink.rel = "prefetch";
                prefetchLink.href = a.href;
                document.head.append(prefetchLink);
            });
        }
    }
})();
