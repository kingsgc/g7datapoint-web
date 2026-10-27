(function () {
    "use strict";

    var root = document.documentElement;

    /* ---------- scroll bar + sticky nav + back to top ---------- */
    var bar = document.getElementById('bar');
    var nav = document.getElementById('nav');
    var up = document.getElementById('up');

    function onScroll() {
        var max = root.scrollHeight - root.clientHeight;
        bar.style.width = (max > 0 ? (root.scrollTop / max) * 100 : 0) + '%';
        nav.classList.toggle('stuck', root.scrollTop > 20);
        up.classList.toggle('on', root.scrollTop > 700);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    up.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ---------- slide-out menu ---------- */
    function closeMenu() { document.body.classList.remove('menu-open'); }
    document.getElementById('open-menu').addEventListener('click', function () {
        document.body.classList.add('menu-open');
    });
    document.getElementById('close-menu').addEventListener('click', closeMenu);
    document.getElementById('veil').addEventListener('click', closeMenu);
    document.querySelectorAll('.pm-links a').forEach(function (a) {
        a.addEventListener('click', closeMenu);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });

    /* ---------- headline word roller ---------- */
    var roll = document.getElementById('roll');
    if (roll) {
        var words = ['data bundles', 'airtime', 'power tokens', 'cable TV', 'exam pins'];
        var idx = 0;
        setInterval(function () {
            idx = (idx + 1) % words.length;
            roll.style.transform = 'translateY(-105%)';
            roll.style.opacity = '0';
            setTimeout(function () {
                roll.style.transition = 'none';
                roll.textContent = words[idx];
                roll.style.transform = 'translateY(105%)';
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        roll.style.transition = '';
                        roll.style.transform = 'none';
                        roll.style.opacity = '1';
                    });
                });
            }, 430);
        }, 2900);
    }

    /* ---------- reveal on scroll ---------- */
    var reveal = new IntersectionObserver(function (rows) {
        rows.forEach(function (row) {
            if (!row.isIntersecting) return;
            row.target.classList.add('seen');
            reveal.unobserve(row.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('[data-r]').forEach(function (el) { reveal.observe(el); });

    /* ---------- number counters ---------- */
    var counters = new IntersectionObserver(function (rows) {
        rows.forEach(function (row) {
            if (!row.isIntersecting) return;
            counters.unobserve(row.target);
            var el = row.target;
            var end = parseFloat(el.dataset.to);
            var dp = parseInt(el.dataset.dp || '0', 10);
            var t0 = null;
            function frame(ts) {
                if (!t0) t0 = ts;
                var p = Math.min((ts - t0) / 1600, 1);
                el.textContent = (end * (1 - Math.pow(1 - p, 3))).toFixed(dp);
                if (p < 1) requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-to]').forEach(function (el) { counters.observe(el); });

    /* ---------- network plan tabs ---------- */
    var tabs = document.querySelectorAll('.tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var pane = document.getElementById(tab.dataset.pane);
            if (!pane) return;
            tabs.forEach(function (t) { t.classList.remove('live-tab'); });
            document.querySelectorAll('.pane').forEach(function (p) { p.classList.remove('live-pane'); });
            tab.classList.add('live-tab');
            pane.classList.add('live-pane');
        });
    });

    /* ---------- FAQ accordion ---------- */
    document.querySelectorAll('.q').forEach(function (item) {
        var btn = item.querySelector('button');
        var ans = item.querySelector('.ans');
        if (item.classList.contains('opened')) ans.style.maxHeight = ans.scrollHeight + 'px';
        btn.addEventListener('click', function () {
            var wasOpen = item.classList.contains('opened');
            document.querySelectorAll('.q.opened').forEach(function (o) {
                o.classList.remove('opened');
                o.querySelector('.ans').style.maxHeight = null;
            });
            if (!wasOpen) {
                item.classList.add('opened');
                ans.style.maxHeight = ans.scrollHeight + 'px';
            }
        });
    });

    /* ---------- highlight the section you are reading ---------- */
    var links = document.querySelectorAll('.nav-links > li > a[href^="#"]');
    var spots = document.querySelectorAll('section[id]');
    var spotObs = new IntersectionObserver(function (rows) {
        rows.forEach(function (row) {
            if (!row.isIntersecting) return;
            links.forEach(function (a) {
                a.classList.toggle('here', a.getAttribute('href') === '#' + row.target.id);
            });
        });
    }, { rootMargin: '-45% 0px -50% 0px' });
    spots.forEach(function (s) { spotObs.observe(s); });

    /* ---------- newsletter stub ---------- */
    var subForm = document.getElementById('sub-form');
    if (subForm) {
        subForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = subForm.querySelector('button');
            btn.textContent = 'You are on the list';
            btn.disabled = true;
            subForm.querySelector('input').value = '';
        });
    }
})();
