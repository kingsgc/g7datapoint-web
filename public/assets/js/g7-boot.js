/* G7DataPoint — brand loading state.
   Shows the G7 logo in #root while the React bundle boots, then fades
   out. Purely additive: if JS fails or takes long, the logo remains and
   the page never renders blank. No dependency on the compiled bundles. */
(function () {
  "use strict";

  var ROOT_ID = "root";
  var LOGO = "/brand/g7-loading.png";
  var MIN_MS = 420;   /* avoid a flash on a warm cache */
  var MAX_MS = 12000; /* hard stop so a hung bundle can't trap the splash */

  var start = Date.now();
  var styleId = "g7-boot-style";

  function splash() {
    var root = document.getElementById(ROOT_ID);
    if (!root) return null;

    /* don't run twice */
    if (document.getElementById(styleId)) return root;
    if (root.querySelector(".g7-boot")) return root;

    if (!document.getElementById(styleId)) {
      var css =
        ".g7-boot{position:fixed;inset:0;z-index:9999;display:flex;" +
        "flex-direction:column;align-items:center;justify-content:center;gap:22px;" +
        "background:#06300c;transition:opacity .45s ease,visibility .45s ease}" +
        ".g7-boot.g7-boot--out{opacity:0;visibility:hidden;pointer-events:none}" +
        ".g7-boot__mark{width:132px;height:132px;object-fit:contain;" +
        "-webkit-user-drag:none;user-select:none;animation:g7-boot-pulse 1.5s ease-in-out infinite}" +
        "@keyframes g7-boot-pulse{0%,100%{transform:scale(1);opacity:.92}" +
        "50%{transform:scale(1.05);opacity:1}}" +
        ".g7-boot__bar{width:132px;height:3px;border-radius:3px;overflow:hidden;" +
        "background:rgba(255,255,255,.16)}" +
        ".g7-boot__fill{display:block;height:100%;width:40%;border-radius:3px;" +
        "background:linear-gradient(90deg,#f0b100,#ffd76a,#f0b100);" +
        "animation:g7-boot-slide 1.15s ease-in-out infinite}" +
        "@keyframes g7-boot-slide{0%{transform:translateX(-105%)}" +
        "100%{transform:translateX(255%)}}" +
        ".g7-boot__name{margin:0;font-family:'Outfit','Segoe UI',Arial,sans-serif;" +
        "font-size:17px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;" +
        "color:rgba(255,255,255,.82)}" +
        "@media (prefers-reduced-motion:reduce){.g7-boot__mark," +
        ".g7-boot__fill{animation:none}.g7-boot__fill{width:100%}}";

      var s = document.createElement("style");
      s.id = styleId;
      s.appendChild(document.createTextNode(css));
      (document.head || document.documentElement).appendChild(s);
    }

    var wrap = document.createElement("div");
    wrap.className = "g7-boot";
    wrap.setAttribute("role", "status");
    wrap.setAttribute("aria-label", "Loading G7DataPoint");

    var img = document.createElement("img");
    img.className = "g7-boot__mark";
    img.src = LOGO;
    img.alt = "";
    img.decoding = "async";

    var bar = document.createElement("div");
    bar.className = "g7-boot__bar";
    bar.innerHTML = '<span class="g7-boot__fill"></span>';

    var name = document.createElement("p");
    name.className = "g7-boot__name";
    name.textContent = "G7DataPoint";

    wrap.appendChild(img);
    wrap.appendChild(bar);
    wrap.appendChild(name);

    /* sibling of root so React mounting #root never touches it */
    root.parentNode.insertBefore(wrap, root.nextSibling);
    return root;
  }

  function dismiss() {
    var wrap = document.querySelector(".g7-boot");
    if (!wrap) return;
    wrap.classList.add("g7-boot--out");
    setTimeout(function () {
      if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
    }, 500);
  }

  function ready(root) {
    /* React has painted: root has children */
    if (root && root.childElementCount > 0) {
      var wait = Math.max(0, MIN_MS - (Date.now() - start));
      setTimeout(dismiss, wait);
      return true;
    }
    return false;
  }

  function boot() {
    var root = splash();
    if (!root) return;

    if (ready(root)) return;

    var elapsed = Date.now() - start;
    if (elapsed > MAX_MS) { dismiss(); return; }

    /* MutationObserver beats polling — fires the instant React commits */
    try {
      var mo = new MutationObserver(function () {
        if (ready(root)) mo.disconnect();
      });
      mo.observe(root, { childList: true });
      setTimeout(function () {
        mo.disconnect();
        if (!ready(root)) dismiss();
      }, Math.max(600, MAX_MS - elapsed));
    } catch (e) {
      var iv = setInterval(function () {
        if (ready(root)) { clearInterval(iv); return; }
        if (Date.now() - start > MAX_MS) { clearInterval(iv); dismiss(); }
      }, 120);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
