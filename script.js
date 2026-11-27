
const CONFIG = {
  branches: {
    cusubamba:    { name: "Local Santa Rosa de Cusubamba", whatsapp: "593998403920", display: "+593 99 840 3920" },
    guayllabamba: { name: "Local Guayllabamba",            whatsapp: "593998707995", display: "+593 99 870 7995" },
  },
  social: {
    // Enlaces de respaldo. Lo normal es ponerlos desde el panel (pestaña "Redes sociales").
    // Si ambos quedan vacíos, el icono correspondiente no se muestra en la página.
    facebook:  "",
    instagram: "",
  },
};


const PRODUCTS = {

  
  educativos: [
    { id: "guayllabamba", name: "Institución Educativa Guayllabamba", logo: "img/logos/institucion1-logo.png", uniformes: { deportivo: "img/uniformes/institucion1-deportivo.jpg", lunes: "img/uniformes/institucion1-lunes.jpg", diario: "img/uniformes/institucion1-diario.jpg" } },
    { id: "sfq", name: "Institución Educativa SFQ", logo: "img/logos/institucion2-logo.jpg", uniformes: { deportivo: "img/uniformes/institucion2-deportivo.jpg", lunes: "img/uniformes/institucion2-lunes.jpg", diario: "img/uniformes/institucion2-diario.jpg" } },
    { id: "ingapirca", name: "Institución Educativa Ingapirca", logo: "img/logos/institucion3-logo.jpg", uniformes: { deportivo: "img/uniformes/institucion3-deportivo.jpg", lunes: "img/uniformes/institucion3-lunes.jpg", diario: "img/uniformes/institucion3-diario.jpg" } },
    { id: "ascazubi", name: "Institución Educativa Ascázubi", logo: "img/logos/institucion4-logo.jpg", uniformes: { deportivo: "img/uniformes/institucion4-deportivo.jpg", lunes: "img/uniformes/institucion4-lunes.jpg", diario: "img/uniformes/institucion4-diario.jpg" } },
    { id: "luis-pasteur", name: "Institución Educativa Luis Pasteur", logo: "", uniformes: { deportivo: "img/uniformes/institucion5-deportivo.jpg", lunes: "img/uniformes/institucion5-lunes.jpg", diario: "img/uniformes/institucion5-diario.jpg" } },
    { id: "junin", name: "Institución Educativa Junín", logo: "", uniformes: { deportivo: "img/uniformes/institucion6-deportivo.jpg", lunes: "img/uniformes/institucion6-lunes.jpg", diario: "img/uniformes/institucion6-diario.jpg" } },
  ].map((p, i) => ({
    ...p,
    desc: "Uniformes de diario y deportivos confeccionados con telas resistentes y acabados de primera calidad.",
    image: "", icon: "cap", chip: "Educativo",
    colors: [["#1c1917"], ["#0a0a0a"], ["#7c2d12"]][i % 3],
  })),
  /* Uniformes deportivos.
     IMPORTANTE: en la página publicada, las fotos de "Ver productos" de Deportivos y Otros
     (y las Promociones) se manejan desde el PANEL (tupagina.com/admin) y se guardan en
     data/contenido.json. Las listas "fotos" de aquí solo se usan si ese archivo no se puede leer
     (por ejemplo, al abrir la página directo desde la computadora). */
  deportivos: [
    { id: "futbol", name: "Uniformes de fútbol",   desc: "Juegos completos para equipos: camiseta, short y medias con tu escudo.", icon: "trophy",
      fotos: [
        "img/deportivos/futbol/futbol1.jpg",
        "img/deportivos/futbol/futbol2.jpg",
        "img/deportivos/futbol/futbol3.jpg",
        "img/deportivos/futbol/futbol4.jpg",
        "img/deportivos/futbol/futbol5.jpg",
        "img/deportivos/futbol/futbol6.jpg",
      ] },
    { id: "camisetas", name: "Camisetas deportivas",   desc: "Camisetas transpirables y ligeras con sublimación personalizada.", icon: "shirt",
      fotos: [
        "img/deportivos/camisetas/camiseta1.jpg",
        "img/deportivos/camisetas/camiseta2.jpg",
        "img/deportivos/camisetas/camiseta3.jpg",
        "img/deportivos/camisetas/camiseta4.jpg",
        "img/deportivos/camisetas/camiseta5.jpg",
        "img/deportivos/camisetas/camiseta6.jpg",
      ] },
    { id: "chompas-promocion", name: "Chompas de promoción",  desc: "Chompas personalizadas para promociones y graduados, con nombre, curso y año.", icon: "shirt", fotos: [] },
    { id: "chompas-chaquetas", name: "Chompas y chaquetas",    desc: "Chaquetas deportivas para delegaciones, clubes y organizaciones.", icon: "shirt", fotos: [] },
  ].map((p, i) => ({ ...p, image: `img/portadas/dep-${p.id}.jpg`, chip: "Deportivo", colors: [["#7c2d12"], ["#1c1917"], ["#0a0a0a"]][i % 3] })),
  /* Otros: igual que en deportivos, las fotos se manejan desde el panel. */
  otros: [
    { id: "gorras-accesorios", name: "Gorras y accesorios", image: "img/portadas/otros-gorras.jpg",   desc: "Gorras y complementos personalizados con tu logo o diseño.", icon: "bag", fotos: [] },
    { id: "estandartes", name: "Estandartes madrinas", image: "img/portadas/otros-madrinas.jpg",  desc: "Estandartes para madrinas de desfiles, inauguraciones y eventos, con el escudo de tu equipo o institución.", icon: "award", fotos: [] },
    { id: "balones", name: "Balones", image: "img/portadas/otros-balones.jpg",                 desc: "Balones para tu equipo o institución, ideales para entrenamientos y campeonatos.", icon: "trophy", fotos: [] },
  ].map((p, i) => ({ ...p, chip: "Otros", colors: [["#0a0a0a"], ["#9a3412"]][i % 2] })),
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
        const [c1] = p.colors || ["#1c1917"];
        // Portada: la que se sube desde el panel; si no hay, la primera foto del álbum
        const cover = p.cover || p.image || (Array.isArray(p.fotos) && p.fotos[0]) || "";
        const media = p.logo
          ? `<img class="card__logo" src="${esc(p.logo)}" alt="Escudo de ${esc(p.name)}" loading="lazy" />`
          : cover
          ? `<img src="${esc(cover)}" alt="${esc(p.name)}" loading="lazy" />`
          : `<svg class="icon" aria-hidden="true"><use href="#i-${esc(p.icon)}"/></svg>`;
        return `
          <article class="card reveal" style="--d:${(i % 3) * 0.08}s">
            <div class="card__media${p.logo ? " card__media--logo" : ""}" style="--c1:${c1}">
              ${media}<span class="card__chip">${esc(p.chip)}</span>
            </div>
            <div class="card__body">
              <h4>${esc(p.name)}</h4>
              <p>${esc(p.desc)}</p>
              ${Array.isArray(p.fotos) ? `<button class="btn btn--outline" data-gallery="${esc(key)}:${i}">Ver productos <svg class="icon"><use href="#i-shirt"/></svg></button>` : ""}
              ${p.uniformes ? `<button class="btn btn--outline" data-uniforms="${esc(key)}:${i}">Ver uniformes <svg class="icon"><use href="#i-shirt"/></svg></button>` : ""}
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
    applySocial();
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
    // Textos legales: muestra en el recuadro solo el documento del enlace pulsado
    $$("[data-legal]").forEach((a) => a.addEventListener("click", (e) => {
      e.preventDefault();
      const modal = $("#legalModal");
      $("#legalTitle").textContent = a.dataset.legal;
      $$("[data-legal-doc]", modal).forEach((doc) => { doc.hidden = doc.dataset.legalDoc !== a.dataset.legal; });
      openModal(modal);
      // El texto siempre empieza desde arriba
      const dialog = $(".modal__dialog", modal);
      dialog.scrollTop = 0;
      setTimeout(() => { dialog.scrollTop = 0; }, 120);
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

  /* ----- Uniformes de cada institución: recuadro con fotos (deportivo, lunes, diario) ----- */
  function initUniforms() {
    const modal = $("#uniformsModal");
    if (!modal) return;
    const title = $("#uniformsTitle"), tabs = $$("[data-uniform-tab]", modal);
    const img = $("#uniformsImg"), empty = $("#uniformsEmpty");
    let current = null;

    function show(type) {
      tabs.forEach((t) => {
        const on = t.dataset.uniformTab === type;
        t.classList.toggle("is-active", on);
        t.setAttribute("aria-selected", on ? "true" : "false");
      });
      const src = current?.uniformes?.[type] || "";
      const label = $(`[data-uniform-tab="${type}"]`, modal).textContent;
      if (src) {
        img.src = src; img.alt = `${label} - ${current.name}`;
        img.hidden = false; empty.hidden = true;
      } else {
        img.removeAttribute("src"); img.hidden = true; empty.hidden = false;
      }
    }

    document.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-uniforms]");
      if (!btn) return;
      e.preventDefault();
      const [key, idx] = btn.dataset.uniforms.split(":");
      current = PRODUCTS[key]?.[Number(idx)];
      if (!current) return;
      title.textContent = current.name;
      show("deportivo");
      openModal(modal);
    });

    tabs.forEach((t) => t.addEventListener("click", () => show(t.dataset.uniformTab)));
    // Si la foto todavía no está en la carpeta, se muestra "Foto próximamente"
    img.addEventListener("error", () => { img.hidden = true; empty.hidden = false; });
  }

  /* ----- Álbum de fotos de cada apartado (uniformes deportivos) ----- */
  function initGallery() {
    const modal = $("#galleryModal");
    if (!modal) return;
    const title = $("#galleryTitle"), img = $("#galleryImg"), empty = $("#galleryEmpty");
    const counter = $("#galleryCount"), thumbs = $("#galleryThumbs");
    const prev = $("#galleryPrev"), next = $("#galleryNext"), stage = $(".gallery__stage", modal);
    let fotos = [], idx = 0, name = "";

    function show(n) {
      if (!fotos.length) return;
      idx = (n + fotos.length) % fotos.length;
      img.src = fotos[idx];
      img.alt = `${name} - foto ${idx + 1} de ${fotos.length}`;
      counter.textContent = `${idx + 1} / ${fotos.length}`;
      $$("button", thumbs).forEach((b, i) => {
        b.classList.toggle("is-active", i === idx);
        if (i === idx) b.scrollIntoView({ block: "nearest", inline: "center" });
      });
    }

    document.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-gallery]");
      if (!btn) return;
      e.preventDefault();
      const [key, i] = btn.dataset.gallery.split(":");
      const p = PRODUCTS[key]?.[Number(i)];
      if (!p) return;
      name = p.name; fotos = (p.fotos || []).filter(Boolean);
      title.textContent = name;
      const has = fotos.length > 0, many = fotos.length > 1;
      img.hidden = !has; empty.hidden = has;
      prev.hidden = next.hidden = counter.hidden = thumbs.hidden = !many;
      if (!has) img.removeAttribute("src");
      thumbs.innerHTML = fotos.map((f, n) =>
        `<button type="button" aria-label="Ver foto ${n + 1}"><img src="${esc(f)}" alt="" loading="lazy" /></button>`).join("");
      $$("button", thumbs).forEach((b, n) => b.addEventListener("click", () => show(n)));
      openModal(modal);
      show(0);
    });

    prev.addEventListener("click", () => show(idx - 1));
    next.addEventListener("click", () => show(idx + 1));
    document.addEventListener("keydown", (e) => {
      if (!modal.classList.contains("is-open") || fotos.length < 2) return;
      if (e.key === "ArrowLeft") show(idx - 1);
      if (e.key === "ArrowRight") show(idx + 1);
    });
    // Deslizar con el dedo en celular
    let x0 = null;
    stage.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener("touchend", (e) => {
      if (x0 === null || fotos.length < 2) return;
      const dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) show(idx + (dx < 0 ? 1 : -1));
    });
  }

  /* ----- Contenido editable desde el panel (fotos de Deportivos/Otros y Promociones) ----- */
  const safePath = (p) => typeof p === "string" && /^(uploads|img)\/[\w\-./]+$/.test(p) && !p.includes("..");
  const todayStr = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  };
  const fmtDate = (s) => { const [y, m, d] = String(s).split("-"); return d && m && y ? `${d}/${m}/${y}` : ""; };
  const fmtMoney = (v) => {
    const n = Number(String(v).replace(",", "."));
    return Number.isFinite(n) && String(v).trim() !== "" ? `$${n.toFixed(2)}` : "";
  };

  function renderPromos(list) {
    const box = $("#promociones"), grid = $("#grid-promociones");
    if (!box || !grid) return;
    const hoy = todayStr();
    const activas = (Array.isArray(list) ? list : []).filter((p) =>
      p && p.activo && p.titulo && p.desde && p.hasta && p.desde <= hoy && hoy <= p.hasta);
    if (!activas.length) { box.hidden = true; grid.innerHTML = ""; return; }
    box.hidden = false;
    grid.innerHTML = activas.map((p) => {
      const media = safePath(p.foto)
        ? `<img src="${esc(p.foto)}" alt="${esc(p.titulo)}" loading="lazy" />`
        : `<svg class="icon" aria-hidden="true"><use href="#i-award"/></svg>`;
      const antes = fmtMoney(p.precio_antes), ahora = fmtMoney(p.precio_ahora);
      const precio = antes && ahora
        ? `<p class="promo__price"><span class="promo__old">Antes ${esc(antes)}</span> <span class="promo__new">Ahora ${esc(ahora)}</span></p>`
        : ahora ? `<p class="promo__price"><span class="promo__new">${esc(ahora)}</span></p>` : "";
      const beneficio = p.beneficio ? `<p class="promo__benefit">${esc(p.beneficio)}</p>` : "";
      const cond = p.condiciones ? `<details class="promo__cond"><summary>Ver condiciones</summary><p>${esc(p.condiciones)}</p></details>` : "";
      return `
        <article class="card promo-card">
          <div class="card__media card__media--promo">${media}<span class="card__chip">Promoción</span></div>
          <div class="card__body">
            <h4>${esc(p.titulo)}</h4>
            ${p.descripcion ? `<p>${esc(p.descripcion)}</p>` : ""}
            ${beneficio}${precio}
            <p class="promo__dates">Válido del ${esc(fmtDate(p.desde))} al ${esc(fmtDate(p.hasta))}</p>
            ${cond}
            <button class="btn btn--dark" data-quote="Promoción: ${esc(p.titulo)}">Lo quiero <svg class="icon"><use href="#i-arrow"/></svg></button>
          </div>
        </article>`;
    }).join("");
  }

  /* ----- Redes sociales: solo se aceptan enlaces reales de Facebook / Instagram ----- */
  const SOCIAL_HOSTS = { facebook: ["facebook.com", "fb.com", "fb.me"], instagram: ["instagram.com"] };
  function validSocial(net, url) {
    try {
      const u = new URL(url);
      if (u.protocol !== "https:" && u.protocol !== "http:") return "";
      const h = u.hostname.toLowerCase();
      return SOCIAL_HOSTS[net].some((d) => h === d || h.endsWith("." + d)) ? u.href : "";
    } catch (err) { return ""; }
  }
  function applySocial(redes) {
    $$("[data-social]").forEach((a) => {
      const net = a.dataset.social;
      const url = validSocial(net, (redes && redes[net]) || CONFIG.social[net] || "");
      if (url) a.href = url;
      a.style.display = url ? "" : "none";   // sin enlace, el icono no se muestra
    });
  }

  /* ----- Si una imagen no existe (404), se muestra un respaldo en vez del icono roto ----- */
  function imageFallback(img) {
    if (!(img instanceof HTMLImageElement)) return;
    if (img.classList.contains("logo__img")) {
      img.insertAdjacentHTML("afterend", '<span class="logo__mark">A</span><span class="logo__text">ANK</span>');
      img.remove();
    } else if (img.closest(".card__media")) {
      const media = img.closest(".card__media");
      media.classList.remove("card__media--logo");
      img.insertAdjacentHTML("afterend", '<svg class="icon" aria-hidden="true"><use href="#i-shirt"/></svg>');
      img.remove();
    } else if (img.closest(".visual")) {
      img.style.display = "none";
    }
  }
  function initImageFallbacks() {
    document.addEventListener("error", (e) => imageFallback(e.target), true);
    // Imágenes que ya fallaron antes de que este código corriera
    $$("img").forEach((img) => { if (img.getAttribute("src") && img.complete && img.naturalWidth === 0) imageFallback(img); });
  }

  /* ----- Fondo del inicio: fotos de deportistas que cambian solas ----- */
  let heroImages = [];   // se llena desde el panel (data.hero)
  const HERO_DEFAULT = ["img/portadas/inicio.jpg"];   // imagen principal del inicio (si el panel no tiene fotos)
  function initHero() {
    const box = $("#heroSlides");
    if (!box) return;
    if (!heroImages.length) heroImages = HERO_DEFAULT;
    initHeroFx(box);
    box.innerHTML = heroImages.map((src, i) =>
      `<img class="hero__slide${i === 0 ? " is-active" : ""}" src="${esc(src)}" alt="" ${i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'} />`).join("");
    // Si una foto no carga, se quita y se sigue con las demás
    $$(".hero__slide", box).forEach((img) => img.addEventListener("error", () => {
      const eraActiva = img.classList.contains("is-active");
      img.remove();
      if (eraActiva) $(".hero__slide", box)?.classList.add("is-active");
    }));
    if (heroImages.length < 2 || reduceMotion) return;   // con una sola foto (o movimiento reducido) queda fija
    setInterval(() => {
      if (document.hidden) return;                        // no cambia si la pestaña está en segundo plano
      const slides = $$(".hero__slide", box);
      if (slides.length < 2) return;
      const cur = slides.findIndex((el) => el.classList.contains("is-active"));
      slides.forEach((el) => el.classList.remove("is-active"));
      slides[(cur + 1) % slides.length].classList.add("is-active");
    }, 5500);
  }

  /* Efectos del fondo del inicio: chispas que suben, brillo que pulsa y movimiento con el mouse */
  function initHeroFx(box) {
    const hero = box.closest(".hero");
    ["hero__fx hero__fx--glow", "hero__fx hero__fx--sweep", "hero__fx hero__fx--vignette"].forEach((c) => {
      const d = document.createElement("div"); d.className = c; hero.insertBefore(d, box.nextSibling);
    });
    // Movimiento reducido, celulares y pantallas táctiles: sin chispas ni seguimiento del mouse
    if (reduceMotion || matchMedia("(max-width: 768px), (hover: none)").matches) return;
    // Movimiento suave de la imagen siguiendo el mouse (solo en computadoras)
    if (matchMedia("(hover: hover) and (pointer: fine)").matches) {
      let tx = 0, ty = 0, cx = 0, cy = 0, raf = 0;
      const loop = () => {
        cx += (tx - cx) * 0.06; cy += (ty - cy) * 0.06;
        box.style.setProperty("--px", `${cx.toFixed(2)}px`); box.style.setProperty("--py", `${cy.toFixed(2)}px`);
        raf = Math.abs(tx - cx) + Math.abs(ty - cy) > 0.05 ? requestAnimationFrame(loop) : 0;
      };
      hero.addEventListener("pointermove", (e) => {
        const r = hero.getBoundingClientRect();
        tx = ((e.clientX - r.left) / r.width - 0.5) * -28; ty = ((e.clientY - r.top) / r.height - 0.5) * -18;
        if (!raf) raf = requestAnimationFrame(loop);
      });
    }
    // Chispas naranjas que suben (canvas)
    const cv = document.createElement("canvas");
    cv.className = "hero__embers"; cv.setAttribute("aria-hidden", "true");
    hero.insertBefore(cv, box.nextSibling);
    const ctx = cv.getContext("2d");
    let W = 0, H = 0, dpr = 1, parts = [];
    const make = (fromBottom) => ({
      x: Math.random() * W, y: fromBottom ? H + Math.random() * 40 : Math.random() * H,
      r: 0.6 + Math.random() * 2.2, vy: 0.25 + Math.random() * 0.9, vx: (Math.random() - 0.5) * 0.35,
      a: 0.35 + Math.random() * 0.6, w: Math.random() * Math.PI * 2, hue: 18 + Math.random() * 22,
    });
    const resize = () => {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      W = hero.clientWidth; H = hero.clientHeight;
      cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.round(Math.min(110, W / 12));
      parts = Array.from({ length: n }, () => make(false));
    };
    resize(); window.addEventListener("resize", resize);
    let visible = true;
    new IntersectionObserver(([en]) => { visible = en.isIntersecting; }).observe(hero);
    const tick = () => {
      requestAnimationFrame(tick);
      if (!visible || document.hidden) return;
      ctx.clearRect(0, 0, W, H);
      ctx.globalCompositeOperation = "lighter";
      parts.forEach((p, i) => {
        p.y -= p.vy; p.w += 0.03; p.x += p.vx + Math.sin(p.w) * 0.35;
        const fade = Math.min(1, p.y / (H * 0.35));
        if (p.y < -10) { parts[i] = make(true); return; }
        const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
        g.addColorStop(0, `hsla(${p.hue}, 100%, 62%, ${p.a * fade})`);
        g.addColorStop(1, `hsla(${p.hue}, 100%, 50%, 0)`);
        ctx.fillStyle = g; ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2); ctx.fill();
      });
      ctx.globalCompositeOperation = "source-over";
    };
    tick();
  }

  async function loadContent() {
    try {
      const ctl = new AbortController();
      const timer = setTimeout(() => ctl.abort(), 4000);
      const r = await fetch(`data/contenido.json?v=${Date.now()}`, { cache: "no-store", signal: ctl.signal });
      clearTimeout(timer);
      if (!r.ok) throw new Error("sin contenido");
      const data = await r.json();
      ["deportivos", "otros"].forEach((key) => {
        (data?.secciones?.[key] || []).forEach((s) => {
          const item = PRODUCTS[key]?.find((x) => x.id === s.id);
          if (!item) return;
          if (Array.isArray(s.fotos)) item.fotos = s.fotos.filter(safePath);
          if (safePath(s.portada)) item.cover = s.portada;
        });
      });
      // Instituciones: portada/escudo y fotos de los tres uniformes
      (data?.secciones?.educativos || []).forEach((s) => {
        const item = PRODUCTS.educativos.find((x) => x.id === s.id);
        if (!item) return;
        if (safePath(s.portada)) item.logo = s.portada;
        ["deportivo", "lunes", "diario"].forEach((t) => {
          if (safePath(s.uniformes?.[t])) item.uniformes[t] = s.uniformes[t];
        });
      });
      applySocial(data.redes);
      if (Array.isArray(data.hero)) heroImages = data.hero.filter(safePath);
      renderPromos(data.promociones);
    } catch (err) {
      renderPromos([]); // sin servidor (archivo abierto en la PC): se usan las fotos del código y no hay promociones
    }
  }

  /* ----- Inicio ----- */
  document.addEventListener("DOMContentLoaded", () => {
    applyConfig();
    initNavbar();
    initFeatureCards();
    initModals();
    initQuote();
    initUniforms();
    initGallery();
    initCounters();
    initParallax();
    initImageFallbacks();
    initReveal();
    // Las tarjetas se dibujan cuando ya llegaron las portadas y fotos del panel (máx. 4 s de espera)
    loadContent().finally(() => { renderCards(); initReveal(); initHero(); });
  });
})();
