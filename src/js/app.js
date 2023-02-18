/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

// Google Tag Manager code.
/* eslint-disable */
window.dataLayer = window.dataLayer || [];
function gtag() {
    dataLayer.push(arguments);
}
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");
/* eslint-enable */

(function () {
    "use strict";

    const toggleAllSources = function () {
        const toggleAllElement = document.getElementById("toggle-all");

        // If it is not expanded, expand it.
        if (toggleAllElement.innerHTML.indexOf("desplega") === -1) {
            document.body.querySelectorAll("details").forEach((e) => {
                e.removeAttribute("open");
            });
            toggleAllElement.innerHTML = toggleAllElement.innerHTML.replace("contrau", "desplega");
        } else {
            // Otherwise collapse it.
            document.body.querySelectorAll("details").forEach((e) => {
                e.setAttribute("open", "true");
            });
            toggleAllElement.innerHTML = toggleAllElement.innerHTML.replace("desplega", "contrau");
        }
    };

    // Search/homepage.
    if (document.getElementById("cerca")) {
        // Remember the search options.
        if (localStorage.getItem("variant") === "2") {
            document.getElementById("variant").checked = false;
        }
        if (localStorage.getItem("sinonim") === "1") {
            document.getElementById("sinonim").checked = true;
        }
        if (localStorage.getItem("equivalent") === "1") {
            document.getElementById("equivalent").checked = true;
        }

        const nextButton = document.querySelector(".page-link[rel=next]");
        const prevButton = document.querySelector(".page-link[rel=prev]");
        const isMac = navigator.userAgent.indexOf("Macintosh") >= 0;
        if (nextButton) {
            nextButton.setAttribute("title", isMac ? "Pàgina següent (Ctrl ⇧ →)" : "Pàgina següent (Ctrl →)");
        }
        if (prevButton) {
            prevButton.setAttribute("title", isMac ? "Pàgina anterior (Ctrl ⇧ ←)" : "Pàgina anterior (Ctrl ←)");
        }

        // Search keyboard shortcuts.
        document.addEventListener("keydown", function (event) {
            if (event.ctrlKey) {
                if (event.key === "/" || event.key === "7") {
                    document.getElementById("cerca").focus();
                } else if (event.key === "ArrowRight" && nextButton) {
                    document.location.assign(nextButton.getAttribute("href"));
                } else if (event.key === "ArrowLeft" && prevButton) {
                    document.location.assign(prevButton.getAttribute("href"));
                }
            }
        });

        // Pager is only available when there are more than 10 results.
        const mostra = document.getElementById("mostra");
        if (mostra) {
            mostra.addEventListener("change", function () {
                document.getElementById("search-form").submit();
            });
        }

        // Store checkboxes values in local storage.
        document.getElementById("variant").addEventListener("change", function () {
            localStorage.setItem("variant", this.checked ? "1" : "2");
        });
        document.getElementById("sinonim").addEventListener("change", function () {
            localStorage.setItem("sinonim", this.checked ? "1" : "");
        });
        document.getElementById("equivalent").addEventListener("change", function () {
            localStorage.setItem("equivalent", this.checked ? "1" : "");
        });
    } else {
        // Keyboard shortcut to go the homepage.
        document.addEventListener("keydown", function (event) {
            if (event.ctrlKey) {
                if (event.key === "/" || event.key === "7") {
                    document.location.assign("/");
                }
            }
        });

        // Play Common Voice on-click in paremiotipus pages.
        Array.prototype.filter.call(document.getElementsByClassName("audio"), function (audio) {
            audio.addEventListener("click", function (event) {
                event.preventDefault();
                this.firstElementChild.play();

                // Preload the other audio files.
                const audioElements = document.querySelectorAll("audio");
                for (let i = 0; i < audioElements.length; i++) {
                    audioElements[i].setAttribute("preload", "auto");
                }
            });
        });

        // Toggle all sources in paremiotipus pages.
        const toggleAllElement = document.getElementById("toggle-all");
        if (toggleAllElement) {
            toggleAllElement.addEventListener("click", function (e) {
                const isExpanded = e.target.innerHTML.indexOf("contrau") !== -1;
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
        const searchBox = document.getElementById("cerca");
        if (searchBox && searchBox.value) {
            // Select the text inside the search box, but not in touch devices.
            if (!("ontouchstart" in window || navigator.maxTouchPoints > 0)) {
                searchBox.select();
            }
        }
    });

    // Show the cookie alert.
    if (localStorage.getItem("accept_cookies") !== "1") {
        document.getElementById("snackbar").classList.remove("d-none");
        document.getElementById("snackbar-action").addEventListener("click", function () {
            document.getElementById("snackbar").remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Toggle hamburger menu.
    document.getElementById("navbar-toggle").addEventListener("click", function () {
        document.getElementById("menu").classList.toggle("collapse");
    });

    // Add class to remove annoying scrolling effect on iOS.
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        document.documentElement.classList.add("ios");
    }
})();
