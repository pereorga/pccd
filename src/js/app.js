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
    dataLayer.push(arguments);
};
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");

(() => {
    // Show the cookie alert if it hasn't been accepted.
    if (localStorage.getItem("accept_cookies") !== "1") {
        const cookieDialog = document.querySelector("#cookie-banner");
        cookieDialog.classList.remove("d-none");
        cookieDialog.querySelector("button").addEventListener("click", () => {
            cookieDialog.remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

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
