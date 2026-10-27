/* G7DataPoint — login page scoping.
   Adds body.g7-login-page while the SPA route is /auth/login so the
   restyle stylesheet only applies there (other SPA pages untouched). */
(function () {
  "use strict";
  var CLASS = "g7-login-page";
  var RE = /^\/auth\/login\/?$/;

  function sync() {
    var on = RE.test(window.location.pathname);
    var body = document.body;
    if (body) body.classList.toggle(CLASS, on);
  }

  /* React Router navigates via history API — hook it, plus a cheap poll */
  try {
    var push = history.pushState;
    var replace = history.replaceState;
    history.pushState = function () {
      var r = push.apply(this, arguments);
      setTimeout(sync, 0);
      return r;
    };
    history.replaceState = function () {
      var r = replace.apply(this, arguments);
      setTimeout(sync, 0);
      return r;
    };
    window.addEventListener("popstate", sync);
  } catch (e) { /* non-fatal */ }

  setInterval(sync, 250);
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", sync);
  } else {
    sync();
  }
})();
