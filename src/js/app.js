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

        // If it is not expanded, expand it.
        if (toggleAllElement.innerHTML.includes("desplega")) {
            for (const element of document.body.querySelectorAll("details")) {
                element.setAttribute("open", "true");
            }
            toggleAllElement.innerHTML = toggleAllElement.innerHTML.replace("desplega", "contrau");
        } else {
            // Otherwise collapse it.
            for (const element of document.body.querySelectorAll("details")) {
                element.removeAttribute("open");
            }
            toggleAllElement.innerHTML = toggleAllElement.innerHTML.replace("contrau", "desplega");
        }
    };

    // Search/homepage.
    if (document.querySelector("#cerca")) {
        // Remember the search options.
        if (localStorage.getItem("variant") === "2") {
            document.querySelector("#variant").checked = false;
        }
        if (localStorage.getItem("sinonim") === "1") {
            document.querySelector("#sinonim").checked = true;
        }
        if (localStorage.getItem("equivalent") === "1") {
            document.querySelector("#equivalent").checked = true;
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
        document.addEventListener("keydown", function (event) {
            if (event.ctrlKey) {
                if (event.key === "/" || event.key === "7") {
                    document.querySelector("#cerca").focus();
                } else if (event.key === "ArrowRight" && nextButton) {
                    document.location.assign(nextButton.getAttribute("href"));
                } else if (event.key === "ArrowLeft" && previousButton) {
                    document.location.assign(previousButton.getAttribute("href"));
                }
            }
        });

        // Pager is only available when there are more than 10 results.
        const mostra = document.querySelector("#mostra");
        if (mostra) {
            mostra.addEventListener("change", function () {
                document.querySelector("#search-form").submit();
            });
        }

        // Store checkboxes values in local storage.
        document.querySelector("#variant").addEventListener("change", function () {
            localStorage.setItem("variant", this.checked ? "1" : "2");
        });
        document.querySelector("#sinonim").addEventListener("change", function () {
            localStorage.setItem("sinonim", this.checked ? "1" : "");
        });
        document.querySelector("#equivalent").addEventListener("change", function () {
            localStorage.setItem("equivalent", this.checked ? "1" : "");
        });
    } else {
        // Keyboard shortcut to go the homepage.
        document.addEventListener("keydown", function (event) {
            if (event.ctrlKey && (event.key === "/" || event.key === "7")) {
                document.location.assign("/");
            }
        });

        // Play Common Voice on-click in paremiotipus pages.
        Array.prototype.filter.call(document.querySelectorAll(".audio"), function (audio) {
            audio.addEventListener("click", function (event) {
                event.preventDefault();
                this.firstElementChild.play();

                // Preload the other audio files.
                const audioElements = document.querySelectorAll("audio");
                for (const audioElement of audioElements) {
                    audioElement.setAttribute("preload", "auto");
                }
            });
        });

        // Toggle all sources in paremiotipus pages.
        const toggleAllElement = document.querySelector("#toggle-all");
        if (toggleAllElement) {
            toggleAllElement.addEventListener("click", function (event) {
                const isExpanded = event.target.innerHTML.includes("contrau");
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
            // Show the toggle-all button only if JS is enabled.
            toggleAllElement.classList.remove("d-none");
        }
    }

    // Ensure the following is executed with browser back/forward navigation.
    window.addEventListener("pageshow", function () {
        // Search page.
        const searchBox = document.querySelector("#cerca");
        // Select the text inside the search box, but not in touch devices.
        if (searchBox && searchBox.value && !("ontouchstart" in window || navigator.maxTouchPoints > 0)) {
            searchBox.select();
        }
    });

    // Show the cookie alert.
    if (localStorage.getItem("accept_cookies") !== "1") {
        document.querySelector("#snackbar").classList.remove("d-none");
        document.querySelector("#snackbar-action").addEventListener("click", function () {
            document.querySelector("#snackbar").remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Toggle hamburger menu.
    document.querySelector("#navbar-toggle").addEventListener("click", function () {
        document.querySelector("#menu").classList.toggle("collapse");
    });

    // Add class to remove annoying scrolling effect on iOS.
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        document.documentElement.classList.add("ios");
    }
})();
