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
// eslint-disable-next-line no-restricted-syntax
const gtag = function () {
    dataLayer.push(arguments);
};
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");

(() => {
    const toggleAllSources = () => {
        const toggleAllButton = document.querySelector("#toggle-all");
        const isExpanded = toggleAllButton.textContent.startsWith("Contrau");
        toggleAllButton.textContent = isExpanded ? "Desplega-ho tot" : "Contrau-ho tot";
        toggleAllButton.setAttribute("title", (isExpanded ? "Mostra" : "Amaga") + " els detalls de cada font");
        for (const element of document.querySelectorAll("details")) {
            element.toggleAttribute("open", !isExpanded);
        }
    };

    const searchBox = document.querySelector("input[type=search]");
    if (searchBox) {
        // We are in the search page / homepage.
        const variantCheckbox = document.querySelector("#variant");
        const sinonimCheckbox = document.querySelector("#sinonim");
        const equivalentCheckbox = document.querySelector("#equivalent");
        const nextButton = document.querySelector("a[rel=next]");
        const previousButton = document.querySelector("a[rel=prev]");
        const mostra = document.querySelector(".pager select");

        // Remember the search options, but only if the search is empty.
        if (searchBox.value === "") {
            variantCheckbox.checked = localStorage.getItem("variant") !== "2";
            sinonimCheckbox.checked = localStorage.getItem("sinonim") === "1";
            equivalentCheckbox.checked = localStorage.getItem("equivalent") === "1";

            // If we are in the homepage, remember pagination if set previously.
            if (!previousButton && nextButton) {
                const storedValue = localStorage.getItem("mostra");
                if (storedValue && storedValue !== mostra.value) {
                    // Request the front page with the preferred pagination.
                    location.assign("/?mostra=" + storedValue);
                }
            }
        }

        // Store values in local storage.
        variantCheckbox.addEventListener("change", () => {
            localStorage.setItem("variant", variantCheckbox.checked ? "1" : "2");
        });
        sinonimCheckbox.addEventListener("change", () => {
            localStorage.setItem("sinonim", sinonimCheckbox.checked ? "1" : "");
        });
        equivalentCheckbox.addEventListener("change", () => {
            localStorage.setItem("equivalent", equivalentCheckbox.checked ? "1" : "");
        });
        mostra.addEventListener("change", () => {
            localStorage.setItem("mostra", mostra.value === "10" ? "" : mostra.value);
            // Submit the form automatically when the pager changes.
            document.querySelector("form[role=search]").submit();
        });

        // Ensure the following is executed with browser back/forward navigation.
        window.addEventListener("pageshow", () => {
            // Ensure browser does not try to remember last form value, as it doesn't help.
            const queryParameters = new URLSearchParams(location.search);
            const searchQuery = queryParameters.get("cerca") || "";
            searchBox.value = searchQuery.trim();

            // On desktop, select the searched value, so it can be replaced by simply typing.
            if (searchBox.value !== "" && !/Android|iPad|iPhone/.test(navigator.userAgent)) {
                searchBox.select();
            }
        });

        searchBox.addEventListener("touchend", () => {
            // On touch devices, select the word on touch, unless it is already selected.
            if (searchBox.value !== "") {
                const isTextSelected = searchBox.selectionStart - searchBox.selectionEnd;
                if (!isTextSelected) {
                    searchBox.select();
                }
            }
        });
    } else {
        // All other pages.
        // Source collapsing, in paremiotipus pages.
        const toggleAllButton = document.querySelector("#toggle-all");
        if (toggleAllButton) {
            // Collapse all sources if this is the user's preference, in paremiotipus pages.
            if (localStorage.getItem("always_expand") === "2") {
                toggleAllSources();
            }
            toggleAllButton.addEventListener("click", (event) => {
                toggleAllSources();
                localStorage.setItem("always_expand", event.target.textContent.startsWith("Desplega") ? "2" : "1");
            });
        }

        // Play Common Voice files on click, in paremiotipus pages.
        for (const audio of document.querySelectorAll(".audio")) {
            audio.addEventListener("click", (event) => {
                event.preventDefault();
                audio.firstElementChild.play();
            });
        }

        // Make elements with role="button" to behave consistently with buttons. This only exist in paremiotipus pages
        // for now (for Common Voice audio).
        for (const button of document.querySelectorAll('[role="button"]')) {
            button.addEventListener("keydown", (event) => {
                if (event.key === " ") {
                    event.preventDefault();
                    button.click();
                }
            });
        }
    }

    // Show the cookie alert if it hasn't been accepted.
    if (localStorage.getItem("accept_cookies") !== "1") {
        const cookieDialog = document.querySelector("#cookie-banner");
        cookieDialog.classList.remove("d-none");
        cookieDialog.querySelector("button").addEventListener("click", () => {
            cookieDialog.remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Toggle hamburger menu on click.
    document.querySelector("header button").addEventListener("click", () => {
        document.querySelector("#menu").classList.toggle("d-none");
    });

    // Prefetch internal links on hover/touch.
    // Inspired by https://github.com/instantpage/instant.page/blob/master/instantpage.js
    const preloadedList = new Set();
    const prefetchLink = (event) => {
        const a = event.currentTarget;
        if (!preloadedList.has(a.href)) {
            preloadedList.add(a.href);
            const link = document.createElement("link");
            link.href = a.href;
            link.rel = "prefetch";
            document.head.append(link);
        }
    };
    for (const a of document.querySelectorAll("a")) {
        if (a.href && a.origin === location.origin) {
            a.addEventListener("mouseenter", prefetchLink);
            a.addEventListener("touchstart", prefetchLink, { passive: true });
        }
    }
})();
