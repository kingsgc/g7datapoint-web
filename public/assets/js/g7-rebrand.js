/* G7DataPoint — runtime rebrand helper for the SPA.
   1. Toggles body.g7-light / body.g7-dark from the live palette so
      g7-app.css can theme chrome per mode.
   2. Remaps legacy brand orange (rgb(253,169,45)/(254,214,128) —
      computed at runtime by MUI alpha(), invisible to plain CSS)
      to the G7 teal pair on backgrounds, text and borders.
   Runs on every SPA screen; debounced + MutationObserver so React
   re-renders are covered. No DOM structure is modified. */
(function () {
  "use strict";

  var KEYS = ["253, 169, 45", "253,169,45", "254, 214, 128", "254,214,128"];
  var MAP = {
    "253, 169, 45": "11, 191, 158",
    "253,169,45": "11,191,158",
    "254, 214, 128": "24, 226, 166",
    "254,214,128": "24,226,166"
  };

  function fix(v) {
    if (!v || v === "none") return null;
    var hit = false;
    for (var i = 0; i < KEYS.length; i++) {
      if (v.indexOf(KEYS[i]) > -1) {
        v = v.split(KEYS[i]).join(MAP[KEYS[i]]);
        hit = true;
      }
    }
    return hit ? v : null;
  }

  function remap(el) {
    if (!el || el.nodeType !== 1) return;
    /* legacy Aychindodata Lottie logo (welcome modal): tag it so
       g7-app.css can hide the artwork and show the G7 logo instead */
    if (el.tagName === "svg") {
      try {
        if (el.getAttribute && el.getAttribute("viewBox") === "0 0 300 300" &&
            el.closest && el.closest(".MuiDialog-paper")) {
          el.classList.add("g7-oldlogo");
        }
      } catch (e) { /* non-fatal */ }
      /* fall through: the root <svg> itself may carry an orange color */
    } else if (el.ownerSVGElement) {
      return;
    }
    /* mark hero stat cards so g7-app.css can give them the solid
       brand gradients — content-based, immune to emotion hash churn */
    if (el.classList && el.classList.contains("MuiCard-root")) {
      var t = el.textContent || "";
      if (t.length < 400) {
        if (t.indexOf("Wallet Balance") > -1 || t.indexOf("Earning Balance") > -1) {
          el.classList.add("g7-hero");
        } else if (t.indexOf("Data Purchased Today") > -1) {
          el.classList.add("g7-hero-deep");
        }
      }
    }
    var cs;
    try { cs = window.getComputedStyle(el); } catch (e) { return; }
    /* NB: set as normal declarations on purpose — g7-app.css uses
       !important hero rules that must keep winning over these remaps. */
    var v;
    if ((v = fix(cs.backgroundImage))) el.style.setProperty("background-image", v);
    if ((v = fix(cs.backgroundColor))) el.style.setProperty("background-color", v);
    if ((v = fix(cs.color))) el.style.setProperty("color", v);
    if ((v = fix(cs.borderTopColor))) el.style.setProperty("border-top-color", v);
  }

  function walk(node) {
    if (!node || node.nodeType !== 1) return;
    remap(node);
    var all = node.querySelectorAll ? node.querySelectorAll("*") : null;
    if (all) for (var i = 0; i < all.length; i++) remap(all[i]);
  }

  /* ---------- drawer brand mark ----------
     The dashboard drawer ships its logo as a compiled inline SVG we
     cannot edit, so prepend the real G7DataPoint mark above it. The
     legacy SVG is hidden by g7-app.css. Idempotent: the guard runs
     before every insert, so repeat flushes are no-ops. */
  var MARK_ID = "g7-drawer-brand";
  function injectDrawerMark() {
    var papers;
    try {
      papers = document.querySelectorAll(".MuiDrawer-paper");
    } catch (e) { return; }
    if (!papers || !papers.length) return;

    for (var i = 0; i < papers.length; i++) {
      var paper = papers[i];
      if (paper.querySelector("#" + MARK_ID)) continue;

      var head = paper.firstElementChild;
      if (!head) continue;

      var mark = document.createElement("div");
      mark.className = "g7-drawer-mark";
      mark.id = MARK_ID;

      var img = document.createElement("img");
      img.src = "/brand/g7-mark-96.png";
      img.alt = "";
      img.decoding = "async";

      var label = document.createElement("span");
      label.textContent = "G7DataPoint";

      mark.appendChild(img);
      mark.appendChild(label);
      head.insertBefore(mark, head.firstChild);
    }
  }

  /* ---------- legacy brand art next to the brand name ----------
     Targets by CONTENT, not by hashed class names: find the element
     whose text is the brand name, walk up to its row, then swap any
     sibling <svg> (the legacy Aychindodata mark) for the real G7
     image. Content-based so it survives emotion-hash churn on
     rebuild, and idempotent via the .g7-swap guard. */
  var BRAND = "G7DataPoint";
  var SWAP_CLS = "g7-brand-swap";

  function swapBrandArt(root) {
    if (!root || root.nodeType !== 1) return;

    var scope = root.querySelectorAll ? root : null;
    if (!scope) return;

    /* every element whose own text is exactly the brand name */
    var all = scope.querySelectorAll("body *");
    for (var i = 0; i < all.length; i++) {
      var el = all[i];
      if (el.classList && el.classList.contains(SWAP_CLS)) continue;
      if ((el.textContent || "").trim() !== BRAND) continue;

      /* climb to the row that holds the logo + the name */
      var row = el;
      for (var up = 0; up < 4 && row.parentElement; up++) {
        row = row.parentElement;
        var svgs = row.querySelectorAll ? row.querySelectorAll("svg") : null;
        if (svgs && svgs.length) { paintRow(row); return; }
      }
    }
  }

  /* paint one row: overlay the real mark on the legacy svg slot */
  function paintRow(row) {
    if (row.querySelector("." + SWAP_CLS)) return;

    var svgs = row.querySelectorAll("svg");
    if (!svgs || !svgs.length) return;

    var holder = document.createElement("span");
    holder.className = SWAP_CLS;

    var img = document.createElement("img");
    img.src = "/brand/g7-mark-96.png";
    img.alt = BRAND;
    img.decoding = "async";
    holder.appendChild(img);

    /* sit immediately before the first legacy svg in that row */
    svgs[0].parentNode.insertBefore(holder, svgs[0]);

    /* hide the legacy artwork, keep any surrounding label text */
    for (var i = 0; i < svgs.length; i++) {
      svgs[i].setAttribute("data-g7-hidden", "1");
    }
  }

  function syncMode() {
    var body = document.body;
    if (!body) return;
    var bg = "rgb(255, 255, 255)";
    try { bg = window.getComputedStyle(body).backgroundColor || bg; } catch (e) {}
    var m = /(\d+),\s*(\d+),\s*(\d+)/.exec(bg);
    var lum = 1;
    if (m) lum = (0.2126 * (+m[1]) + 0.7152 * (+m[2]) + 0.0722 * (+m[3])) / 255;
    var dark = lum < 0.45;
    body.classList.toggle("g7-dark", dark);
    body.classList.toggle("g7-light", !dark);
  }

  var pending = [];
  var timer = null;
  function flush() {
    timer = null;
    syncMode();
    injectDrawerMark();
    var list = pending;
    pending = [];
    for (var i = 0; i < list.length; i++) walk(list[i]);
    try { swapBrandArt(document.body); } catch (e) { /* non-fatal */ }
  }
  function schedule(node) {
    pending.push(node);
    if (!timer) timer = setTimeout(flush, 200);
  }

  function start() {
    if (!document.body) return setTimeout(start, 300);
    syncMode();
    walk(document.body);
    /* Brand work must run on first paint too — React may have already
       committed the drawer before this script executes, in which case
       no mutation ever fires to trigger flush(). */
    injectDrawerMark();
    try { swapBrandArt(document.body); } catch (e) { /* non-fatal */ }
    try {
      var mo = new MutationObserver(function (muts) {
        for (var i = 0; i < muts.length; i++) {
          if (muts[i].type === "attributes") {
            if (muts[i].target && muts[i].target.nodeType === 1) schedule(muts[i].target);
          } else {
            var added = muts[i].addedNodes;
            for (var j = 0; j < added.length; j++) {
              if (added[j].nodeType === 1) schedule(added[j]);
            }
          }
        }
      });
      mo.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["class", "style"]
      });
    } catch (e) { /* non-fatal */ }
    // palette/theme toggles don't always mutate DOM — resync occasionally
    setInterval(syncMode, 2000);
    /* late-mounted drawers (route change without a class/style mutation)
       still need the brand pass, so retry it briefly then stop */
    var tries = 0;
    var iv = setInterval(function () {
      injectDrawerMark();
      try { swapBrandArt(document.body); } catch (e) {}
      if (++tries >= 10) clearInterval(iv);
    }, 400);
  }

  start();
})();
