/* phiz: renders [data-phiz] elements with vega-embed, and re-renders when their spec changes (Livewire morphs). */
(function () {
  if (window.Phiz) return;
  var views = new WeakMap();
  var dark = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function parse(json, fallback) {
    if (!json) return fallback;
    try { return JSON.parse(json); } catch (e) { console.error('phiz: invalid JSON', e); return fallback; }
  }

  function target(el) {
    var view = el.querySelector(':scope > [data-phiz-view]');
    if (!view) {
      view = document.createElement('div');
      view.setAttribute('data-phiz-view', '');
      view.setAttribute('wire:ignore', '');
      el.appendChild(view);
    }
    return view;
  }

  function render(el) {
    var embed = Phiz.embed || window.vegaEmbed;
    if (!embed) { pending = true; return; }
    var json = el.getAttribute('data-phiz');
    var optionsJson = el.getAttribute('data-phiz-options') || '';
    var key = json + '\u0000' + optionsJson + '\u0000' + (dark && dark.matches ? 'dark' : 'light');
    var state = views.get(el);
    if (state && state.key === key) return;
    var spec = parse(json, null);
    if (!spec) return;
    var options = parse(optionsJson, {});
    if (options.theme === 'auto') {
      if (dark && dark.matches) options.theme = 'dark'; else delete options.theme;
    }
    if (state && state.view) state.view.finalize();
    var token = {};
    views.set(el, { key: key, token: token, view: null });
    Promise.resolve(embed(target(el), spec, options)).then(function (result) {
      var current = views.get(el);
      if (!current || current.token !== token) { result.view.finalize(); return; }
      current.view = result.view;
      el.dispatchEvent(new CustomEvent('phiz:rendered', { detail: { view: result.view }, bubbles: true }));
    }).catch(function (error) {
      console.error('phiz:', error);
      target(el).textContent = 'Chart error: ' + error.message;
    });
  }

  function scan(root) {
    if (root.nodeType !== 1) return;
    if (root.hasAttribute('data-phiz')) render(root);
    root.querySelectorAll('[data-phiz]').forEach(render);
  }

  function release(root) {
    if (root.nodeType !== 1) return;
    var nodes = root.hasAttribute('data-phiz') ? [root] : [];
    root.querySelectorAll('[data-phiz]').forEach(function (n) { nodes.push(n); });
    nodes.forEach(function (n) {
      if (n.isConnected) return;
      var state = views.get(n);
      if (state && state.view) state.view.finalize();
      views.delete(n);
    });
  }

  var pending = false;
  var Phiz = window.Phiz = {
    embed: null,
    render: render,
    scan: function () { scan(document.documentElement); },
    view: function (el) { var s = views.get(el); return s ? s.view : null; }
  };

  new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      if (m.type === 'attributes') { render(m.target); return; }
      m.addedNodes.forEach(scan);
      m.removedNodes.forEach(release);
    });
  }).observe(document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['data-phiz', 'data-phiz-options'] });

  if (dark && dark.addEventListener) dark.addEventListener('change', function () { Phiz.scan(); });

  function start() { pending = false; Phiz.scan(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
  window.addEventListener('load', function () { if (pending) start(); });
})();
