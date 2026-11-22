
const CONFIG = {
  branches: {
    cusubamba:    { name: "Local Santa Rosa de Cusubamba", whatsapp: "593998403920", display: "+593 99 840 3920" },
    guayllabamba: { name: "Local Guayllabamba",            whatsapp: "593998707995", display: "+593 99 870 7995" },
  },
  social: {
    facebook:  "https://www.facebook.com/",
    instagram: "https://www.instagram.com/",
  },
};


const PRODUCTS = {
  educativos: [1, 2, 3, 4, 5, 6].map((n, i) => ({
    name: `Institución Educativa ${n}`,
    desc: "Uniformes de diario y deportivos confeccionados con telas resistentes y acabados de primera calidad.",
    image: "", icon: "cap", chip: "Educativo",
    colors: [["#111827"], ["#0a0a0a"], ["#172554"]][i % 3],
  })),
  deportivos: [
    { name: "Camisetas deportivas",   desc: "Camisetas transpirables y ligeras con sublimación personalizada.", icon: "shirt",  },
    { name: "Conjuntos de buzo",      desc: "Buzos completos de chaqueta y pantalón para entrenamiento y uso diario.", icon: "shirt" },
    { name: "Uniformes de fútbol",    desc: "Juegos completos para equipos: camiseta, short y medias con tu escudo.", icon: "trophy" },
    { name: "Uniformes de baloncesto", desc: "Jerseys y shorts de corte atlético, con números y nombres personalizados.", icon: "trophy" },
    { name: "Shorts y licras",         desc: "Prendas elásticas y cómodas para academias, gimnasios y disciplinas varias.", icon: "shirt" },
    { name: "Chompas y chaquetas",     desc: "Chaquetas deportivas para delegaciones, clubes y organizaciones.", icon: "shirt" },
  ].map((p, i) => ({ ...p, image: "", chip: "Deportivo", colors: [["#172554"], ["#111827"], ["#0a0a0a"]][i % 3] })),
  otros: [
    { name: "Mandiles y batas",   desc: "Mandiles escolares y batas de laboratorio o trabajo en distintos colores.", icon: "shirt" },
    { name: "Polos corporativos", desc: "Polos bordados o estampados con el logotipo de tu empresa o institución.", icon: "shirt" },
    { name: "Gorras y accesorios", desc: "Gorras, bolsos y complementos personalizados a tu medida.", icon: "bag" },
    { name: "Prendas a medida",    desc: "Diseñamos y confeccionamos la prenda que necesitas según tus especificaciones.", icon: "bag" },
  ].map((p, i) => ({ ...p, image: "", chip: "Otros", colors: [["#0a0a0a"], ["#1e3a8a"]][i % 2] })),
};


(() => {
  "use strict";

  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const reduceMotion = matchMedia("(prefers-reduced-motion: reduce)").matches;


  document.documentElement.classList.remove("no-js");


  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));


  function renderCards() {
    Object.entries(PRODUCTS).forEach(([key, items]) => {
      const grid = $(`#grid-${key}`);
      if (!grid) return;
      grid.innerHTML = items.map((p, i) => {
        const [c1] = p.colors || ["#111827"];
        const media = p.image
          ? `<img src="${esc(p.image)}" alt="${esc(p.name)}" loading="lazy" />`
          : `<svg class="icon" aria-hidden="true"><use href="#i-${esc(p.icon)}"/></svg>`;
        return `
          <article class="card reveal" style="--d:${(i % 3) * 0.08}s">
            <div class="card__media" style="--c1:${c1}">
              ${media}<span class="card__chip">${esc(p.chip)}</span>
            </div>
            <div class="card__body">
              <h4>${esc(p.name)}</h4>
              <p>${esc(p.desc)}</p>
              <button class="btn btn--dark" data-quote="${esc(p.name)}">Solicitar Cotización <svg class="icon"><use href="#i-arrow"/></svg></button>
            </div>
          </article>`;
      }).join("");
    });
  }


  function waLink(branchKey, text = "Hola ANK, quisiera más información sobre sus uniformes.") {
    const b = CONFIG.branches[branchKey];
    return `https://wa.me/${b.whatsapp}?text=${encodeURIComponent(text)}`;
  }
  function applyConfig() {
    $$("[data-wa]").forEach((a) => { a.href = waLink(a.dataset.wa); });
    $$("[data-phone]").forEach((el) => { el.textContent = CONFIG.branches[el.dataset.phone].display; });
    $$("[data-social]").forEach((a) => { a.href = CONFIG.social[a.dataset.social] || "#"; });
    const y = $("#year"); if (y) y.textContent = new Date().getFullYear();
  }


  function initNavbar() {
    const navbar = $("#navbar"), nav = $("#nav"), burger = $("#burger");
    const onScroll = () => navbar.classList.toggle("is-scrolled", window.scrollY > 30);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    const setMenu = (open) => {
      nav.classList.toggle("is-open", open);
      burger.setAttribute("aria-expanded", open);
      burger.innerHTML = `<svg class="icon"><use href="#i-${open ? "x" : "menu"}"/></svg>`;
      document.body.classList.toggle("no-scroll", open);
    };
    burger.addEventListener("click", () => setMenu(!nav.classList.contains("is-open")));
    $$("a, button", nav).forEach((el) => el.addEventListener("click", () => setMenu(false)));
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") setMenu(false); });


    const map = { inicio: ["inicio", "historia", "productos"], categorias: ["categorias"], contacto: ["contacto"] };
    const links = $$(".nav__link");
    const spy = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        const key = Object.keys(map).find((k) => map[k].includes(en.target.id));
        links.forEach((l) => l.classList.toggle("is-active", l.dataset.section === key));
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    $$("main section[id]").forEach((s) => spy.observe(s));
  }


  function initFeatureCards() {
    $$("[data-goto]").forEach((card) => {
      const go = () => document.querySelector(card.dataset.goto)?.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth" });
      card.addEventListener("click", (e) => { e.preventDefault(); go(); });
      card.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); go(); } });
    });
  }


  function initReveal() {
    const items = $$(".reveal");
    if (!("IntersectionObserver" in window) || reduceMotion) { items.forEach((i) => i.classList.add("is-visible")); return; }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("is-visible"); io.unobserve(en.target); } });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    items.forEach((i) => io.observe(i));
  }

  function initCounters() {
    const fmt = (n, sep) => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep || "");
    const run = (el) => {
      const target = +el.dataset.count, prefix = el.dataset.prefix || "", sep = el.dataset.sep || "";
      if (reduceMotion) { el.textContent = prefix + fmt(target, sep); return; }
      const dur = 1600, t0 = performance.now();
      const tick = (t) => {
        const p = Math.min((t - t0) / dur, 1), eased = 1 - Math.pow(1 - p, 4);
        el.textContent = prefix + fmt(Math.round(target * eased), sep);
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) { run(en.target); io.unobserve(en.target); } });
    }, { threshold: 0.6 });
    $$("[data-count]").forEach((el) => io.observe(el));
  }


  function initParallax() {
    if (reduceMotion) return;
    const els = $$("[data-parallax]");
    let ticking = false;
    const update = () => {
      els.forEach((el) => {
        const r = el.getBoundingClientRect();
        if (r.bottom < 0 || r.top > innerHeight) return;
        const offset = (r.top + r.height / 2 - innerHeight / 2) * parseFloat(el.dataset.parallax);
        el.style.transform = `translateY(${offset.toFixed(1)}px)`;
      });
      ticking = false;
    };
    window.addEventListener("scroll", () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
  }

  /* ----- Modales (accesibles: Esc, foco, clic fuera) ----- */
  let lastFocus = null;
  function openModal(modal) {
    lastFocus = document.activeElement;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
    setTimeout(() => ($(".branch-option, button.btn", modal) || $(".modal__close", modal))?.focus(), 80);
  }
  function closeModal(modal) {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
    lastFocus?.focus?.();
  }
  function initModals() {
    $$(".modal").forEach((m) => {
      $$("[data-close]", m).forEach((b) => b.addEventListener("click", () => closeModal(m)));
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") $$(".modal.is-open").forEach(closeModal);
      // Mantener el foco dentro del modal abierto
      if (e.key === "Tab") {
        const m = $(".modal.is-open"); if (!m) return;
        const f = $$("a[href],button:not([disabled]),input,select,textarea", m).filter((x) => x.offsetParent !== null);
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    // Textos legales (de ejemplo)
    $$("[data-legal]").forEach((a) => a.addEventListener("click", (e) => {
      e.preventDefault();
      $("#legalTitle").textContent = a.dataset.legal;
      openModal($("#legalModal"));
    }));
  }

  /* ----- Cotización: el usuario elige el local y se abre el WhatsApp de ese local ----- */
  function initQuote() {
    const modal = $("#quoteModal"), lead = $("#quoteLead");
    const options = $$("[data-local]", modal);
    const defaultLead = lead.textContent;

    // Cualquier botón [data-quote] abre el modal; si trae un producto, se incluye en el mensaje
    document.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-quote]");
      if (!btn) return;
      e.preventDefault();
      const product = btn.dataset.quote;
      const text = product
        ? `Hola ANK, quisiera solicitar una cotización de: ${product}.`
        : "Hola ANK, quisiera solicitar una cotización.";
      lead.textContent = product ? `Cotización de: ${product}. Elige el local y te atenderemos por WhatsApp.` : defaultLead;
      // Son enlaces reales (<a target="_blank">): el navegador no los bloquea como ventanas emergentes
      options.forEach((o) => { o.href = waLink(o.dataset.local, text); });
      openModal(modal);
    });

    options.forEach((o) => o.addEventListener("click", () => setTimeout(() => closeModal(modal), 150)));
  }

  /* ----- Inicio ----- */
  document.addEventListener("DOMContentLoaded", () => {
    renderCards();
    applyConfig();
    initNavbar();
    initFeatureCards();
    initModals();
    initQuote();
    initReveal();      // después de renderCards para incluir las tarjetas
    initCounters();
    initParallax();
  });
})();
