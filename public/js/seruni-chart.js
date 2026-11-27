/* Grafik garis SVG tanpa dependensi. Nilai null memutus garis (tanggal tidak tercatat). */
window.SeruniChart = (function () {
  var NS = 'http://www.w3.org/2000/svg';
  function s(name, attrs, parent) {
    var n = document.createElementNS(NS, name);
    for (var k in attrs) n.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(n);
    return n;
  }
  function nice(max) {
    if (max <= 0) return 4;
    var p = Math.pow(10, Math.floor(Math.log10(max))), f = max / p;
    return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * p;
  }

  function line(host, cfg) {
    host.innerHTML = '';
    var W = 860, H = 380, m = { l: 46, r: 16, t: 34, b: 52 }, iw = W - m.l - m.r, ih = H - m.t - m.b;
    var n = cfg.x.length, step = n > 1 ? iw / (n - 1) : iw;
    var max = 0;
    cfg.series.forEach(function (se) { se.values.forEach(function (v) { if (v != null && v > max) max = v; }); });
    var top = cfg.yMax || nice(max * 1.08), ticks = top <= 5 ? top : 5;
    var X = function (i) { return m.l + (n > 1 ? i * step : iw / 2); };
    var Y = function (v) { return m.t + ih - (v / top) * ih; };

    var svg = s('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': cfg.label || 'Grafik garis' }, host);
    (cfg.bands || []).forEach(function (b) {
      var x0 = X(b.from) - step / 2, x1 = X(b.to) + step / 2;
      x0 = Math.max(x0, m.l); x1 = Math.min(x1, m.l + iw);
      s('rect', { x: x0, y: m.t, width: x1 - x0, height: ih, fill: b.fill }, svg);
      if (b.edge) s('line', { x1: x0, x2: x0, y1: m.t, y2: m.t + ih, stroke: '#111', 'stroke-dasharray': '3 4' }, svg);
      var t = s('text', { x: (x0 + x1) / 2, y: m.t - 12, 'text-anchor': 'middle', 'font-size': 12, 'font-weight': 700, fill: '#111' }, svg);
      t.textContent = b.label;
    });
    if (cfg.mark != null && cfg.mark >= 0 && cfg.mark < n) {
      s('rect', { x: X(cfg.mark) - Math.max(step / 2, 5), y: m.t, width: Math.max(step, 10), height: ih, fill: 'rgba(255,122,0,.28)' }, svg);
      s('line', { x1: X(cfg.mark), x2: X(cfg.mark), y1: m.t, y2: m.t + ih, stroke: '#FF7A00', 'stroke-width': 2 }, svg);
    }
    for (var g = 0; g <= ticks; g++) {
      var v = (top / ticks) * g, y = Y(v);
      s('line', { x1: m.l, x2: m.l + iw, y1: y, y2: y, stroke: 'rgba(17,17,17,.16)' }, svg);
      var tl = s('text', { x: m.l - 8, y: y + 4, 'text-anchor': 'end', 'font-size': 12, fill: '#444' }, svg);
      tl.textContent = Math.round(v * 10) / 10;
    }
    if (cfg.yTitle) {
      var yt = s('text', { transform: 'translate(12 ' + (m.t + ih / 2) + ') rotate(-90)', 'text-anchor': 'middle', 'font-size': 12, fill: '#444' }, svg);
      yt.textContent = cfg.yTitle;
    }
    var every = Math.max(1, Math.ceil(n / 14));
    cfg.x.forEach(function (p, i) {
      if ((n - 1 - i) % every !== 0) return;   // hitung dari kanan: label terakhir selalu tampil, tanpa tumpang tindih
      var t = s('text', { x: X(i), y: H - m.b + 20, 'text-anchor': 'end', 'font-size': 12, fill: '#444', transform: 'rotate(-40 ' + X(i) + ' ' + (H - m.b + 20) + ')' }, svg);
      t.textContent = p.label;
    });
    s('line', { x1: m.l, x2: m.l + iw, y1: m.t + ih, y2: m.t + ih, stroke: '#111', 'stroke-width': 2 }, svg);

    cfg.series.forEach(function (se) {
      var d = '', pen = false;
      se.values.forEach(function (v, i) {
        if (v == null) { pen = false; return; }
        d += (pen ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(v).toFixed(1) + ' ';
        pen = true;
      });
      if (se.type === 'bar') {
        var bw = Math.max(Math.min(step * 0.62, 34), 4);
        se.values.forEach(function (v, i) {
          if (v == null) return;
          var h = v === 0 ? 3 : (v / top) * ih;   // 0 tercatat digambar sebagai garis tipis
          s('rect', { x: X(i) - bw / 2, y: m.t + ih - h, width: bw, height: h, fill: v === 0 ? '#111' : se.color, stroke: '#111', 'stroke-width': 1.5 }, svg);
        });
        return;
      }
      if (se.type === 'step') {
        se.values.forEach(function (v, i) { if (v != null) s('line', { x1: X(i) - 5, x2: X(i) + 5, y1: Y(v), y2: Y(v), stroke: se.color, 'stroke-width': 3, 'stroke-linecap': 'round' }, svg); });
        return;
      }
      s('path', { d: d, fill: 'none', stroke: se.color, 'stroke-width': se.width || 2.5, 'stroke-dasharray': se.dash || '', 'stroke-linejoin': 'round' }, svg);
      if (n <= 60) se.values.forEach(function (v, i) {
        if (v == null) return;
        var hollow = se.hollowFrom != null && i >= se.hollowFrom;
        s('circle', { cx: X(i), cy: Y(v), r: 3.6, fill: hollow ? '#fff' : se.color, stroke: se.color, 'stroke-width': 2 }, svg);
      });
    });

    var tip = document.createElement('div');
    tip.className = 'tip';
    host.appendChild(tip);
    cfg.x.forEach(function (p, i) {
      var r = s('rect', { x: X(i) - Math.max(step / 2, 6), y: m.t, width: Math.max(step, 12), height: ih, fill: 'transparent', style: 'cursor:pointer' }, svg);
      r.addEventListener('mousemove', function (e) {
        tip.innerHTML = cfg.tooltip ? cfg.tooltip(i) : p.label;
        var b = host.getBoundingClientRect(), x = e.clientX - b.left + 14, y = e.clientY - b.top - 10;
        if (x + tip.offsetWidth > b.width - 6) x = e.clientX - b.left - tip.offsetWidth - 14;
        tip.style.left = x + 'px'; tip.style.top = Math.max(y, 4) + 'px'; tip.style.opacity = 1;
      });
      r.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
      if (cfg.onSelect) r.addEventListener('click', function () { cfg.onSelect(i); });
    });
  }
  return { line: line };
})();
