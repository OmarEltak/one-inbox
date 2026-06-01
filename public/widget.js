/**
 * OT1-Pro — Web Chat Widget (v1)
 *
 * Embed:
 *   <script src="https://your-domain/widget.js" data-widget-id="wc_xxx" defer></script>
 *
 * What this does:
 *   - Mints / resumes a visitor_id in localStorage so the same visitor sees their thread on return.
 *   - Renders a floating bubble bottom-right; click to open the chat panel.
 *   - Sends visitor messages to /api/webchat/{widget}/messages.
 *   - Polls /api/webchat/{widget}/messages every 3s (every 1.5s while panel open) to fetch agent replies.
 *
 * No build step, no runtime deps. Inline CSS so it can't be broken by host-site styles.
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Boot — find our own <script> tag, pull config out of data-* attrs
  // ---------------------------------------------------------------------------
  var script = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    for (var i = scripts.length - 1; i >= 0; i--) {
      if (scripts[i].src && scripts[i].src.indexOf('widget.js') !== -1) return scripts[i];
    }
    return null;
  })();

  if (!script) {
    console.warn('[OneInbox] widget.js: could not locate own <script> tag');
    return;
  }

  var widgetId = script.getAttribute('data-widget-id');
  if (!widgetId) {
    console.warn('[OneInbox] widget.js: data-widget-id is required');
    return;
  }

  // Derive API base from the script's own src so the snippet works on any host.
  var apiBase = (function () {
    try {
      var u = new URL(script.src);
      return u.protocol + '//' + u.host;
    } catch (_) {
      return '';
    }
  })();

  // ---------------------------------------------------------------------------
  // State
  // ---------------------------------------------------------------------------
  var STORAGE_KEY = 'oneinbox_visitor_' + widgetId;
  var visitorId = localStorage.getItem(STORAGE_KEY) || null;
  var lastSeenMs = 0;        // server timestamp of newest message we've rendered
  var pollHandle = null;
  var pollInterval = 3000;   // ms; tightens to 1500 while panel is open
  var panelOpen = false;
  var config = { theme_color: '#22c55e', greeting: 'Hi! How can we help?', widget_name: 'Chat' };
  var renderedIds = Object.create(null);

  // ---------------------------------------------------------------------------
  // DOM helpers
  // ---------------------------------------------------------------------------
  function el(tag, props, children) {
    var node = document.createElement(tag);
    if (props) {
      for (var k in props) {
        if (k === 'style') Object.assign(node.style, props[k]);
        else if (k === 'on') for (var ev in props.on) node.addEventListener(ev, props.on[ev]);
        else if (k in node) node[k] = props[k];
        else node.setAttribute(k, props[k]);
      }
    }
    (children || []).forEach(function (c) {
      node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    });
    return node;
  }

  function fmtTime(iso) {
    try {
      var d = new Date(iso);
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (_) {
      return '';
    }
  }

  // ---------------------------------------------------------------------------
  // API
  // ---------------------------------------------------------------------------
  function api(path, opts) {
    return fetch(apiBase + '/api/webchat/' + encodeURIComponent(widgetId) + path, Object.assign({
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
    }, opts || {})).then(function (r) {
      if (!r.ok) return r.json().catch(function () { return {}; }).then(function (b) {
        var err = new Error(b.error || ('http_' + r.status));
        err.status = r.status;
        throw err;
      });
      return r.json();
    });
  }

  function ensureVisitor() {
    return api('/visitor', {
      method: 'POST',
      body: JSON.stringify({ visitor_id: visitorId })
    }).then(function (data) {
      visitorId = data.visitor_id;
      localStorage.setItem(STORAGE_KEY, visitorId);
      config.theme_color = data.theme_color || config.theme_color;
      config.greeting = data.greeting || config.greeting;
      config.widget_name = data.widget_name || config.widget_name;
      // Backfill thread for returning visitors so the panel isn't empty.
      (data.history || []).forEach(function (m) { renderMessage(m, /*scroll=*/false); });
      return data;
    });
  }

  function sendMessage(content) {
    return api('/messages', {
      method: 'POST',
      body: JSON.stringify({ visitor_id: visitorId, content: content })
    });
  }

  function poll() {
    if (!visitorId) return Promise.resolve();
    var qs = lastSeenMs > 0 ? ('?visitor_id=' + visitorId + '&since=' + lastSeenMs) : ('?visitor_id=' + visitorId);
    return api('/messages' + qs).then(function (data) {
      (data.messages || []).forEach(function (m) { renderMessage(m, /*scroll=*/true); });
    }).catch(function (e) {
      // Swallow transient errors so the poller keeps running. 404 = widget removed; stop.
      if (e.status === 404) stopPolling();
    });
  }

  function startPolling() {
    if (pollHandle) clearInterval(pollHandle);
    pollHandle = setInterval(poll, pollInterval);
  }

  function stopPolling() {
    if (pollHandle) clearInterval(pollHandle);
    pollHandle = null;
  }

  function setPollInterval(ms) {
    pollInterval = ms;
    if (pollHandle) startPolling();
  }

  // ---------------------------------------------------------------------------
  // UI — built imperatively so we don't ship a framework. Inline styles only.
  // ---------------------------------------------------------------------------
  var ui = {};

  function buildUI() {
    // Container — fixed bottom-right, isolated z-index so we sit above modals.
    var root = el('div', {
      id: 'oneinbox-widget-root',
      style: {
        position: 'fixed', right: '20px', bottom: '20px', zIndex: '2147483000',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
      }
    });

    // Bubble (always visible)
    var bubble = el('button', {
      type: 'button',
      'aria-label': 'Open chat',
      on: { click: togglePanel },
      style: {
        width: '60px', height: '60px', borderRadius: '50%', border: 'none',
        background: config.theme_color, color: 'white', cursor: 'pointer',
        boxShadow: '0 8px 24px rgba(0,0,0,0.18)', display: 'flex',
        alignItems: 'center', justifyContent: 'center',
        transition: 'transform .15s ease', outline: 'none'
      }
    });
    bubble.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
    bubble.onmouseenter = function () { bubble.style.transform = 'scale(1.06)'; };
    bubble.onmouseleave = function () { bubble.style.transform = 'scale(1)'; };

    // Panel (hidden by default)
    var panel = el('div', {
      style: {
        position: 'absolute', right: '0', bottom: '76px',
        width: '360px', maxWidth: 'calc(100vw - 40px)',
        height: '520px', maxHeight: 'calc(100vh - 120px)',
        background: 'white', borderRadius: '14px',
        boxShadow: '0 18px 50px rgba(0,0,0,0.22)',
        display: 'none', flexDirection: 'column', overflow: 'hidden'
      }
    });

    var header = el('div', {
      style: {
        background: config.theme_color, color: 'white',
        padding: '14px 16px', display: 'flex',
        alignItems: 'center', justifyContent: 'space-between'
      }
    }, [
      el('div', { style: { fontWeight: '600', fontSize: '15px' }, textContent: config.widget_name }),
      (function () {
        var x = el('button', {
          type: 'button',
          'aria-label': 'Close chat',
          on: { click: togglePanel },
          style: {
            background: 'transparent', border: 'none', color: 'white',
            cursor: 'pointer', fontSize: '22px', lineHeight: '1', padding: '0 4px'
          }
        });
        x.textContent = '×';
        return x;
      })()
    ]);

    var greeting = el('div', {
      style: {
        padding: '10px 14px', background: '#f5f7fa', color: '#475569',
        fontSize: '13px', borderBottom: '1px solid #e5e7eb'
      },
      textContent: config.greeting
    });

    var thread = el('div', {
      id: 'oneinbox-thread',
      style: {
        flex: '1', overflowY: 'auto', padding: '12px 14px',
        background: '#fafafa', display: 'flex', flexDirection: 'column', gap: '6px'
      }
    });

    var inputRow = el('form', {
      on: { submit: function (e) { e.preventDefault(); handleSubmit(); } },
      style: {
        display: 'flex', gap: '8px', padding: '10px 12px',
        borderTop: '1px solid #e5e7eb', background: 'white'
      }
    });
    var input = el('input', {
      type: 'text',
      placeholder: 'Type a message…',
      maxLength: 4000,
      style: {
        flex: '1', padding: '10px 12px', borderRadius: '20px',
        border: '1px solid #d1d5db', fontSize: '14px', outline: 'none',
        background: 'white'
      }
    });
    var sendBtn = el('button', {
      type: 'submit',
      'aria-label': 'Send',
      style: {
        background: config.theme_color, color: 'white', border: 'none',
        width: '40px', height: '40px', borderRadius: '50%',
        cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center'
      }
    });
    sendBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
    inputRow.appendChild(input);
    inputRow.appendChild(sendBtn);

    var footer = el('div', {
      style: {
        textAlign: 'center', fontSize: '10px', color: '#9ca3af',
        padding: '4px 0 8px', background: 'white'
      },
      textContent: 'Powered by OT1-Pro'
    });

    panel.appendChild(header);
    panel.appendChild(greeting);
    panel.appendChild(thread);
    panel.appendChild(inputRow);
    panel.appendChild(footer);

    root.appendChild(panel);
    root.appendChild(bubble);

    document.body.appendChild(root);

    ui = { root: root, bubble: bubble, panel: panel, thread: thread, input: input, header: header, greeting: greeting, sendBtn: sendBtn };

    function handleSubmit() {
      var text = input.value.trim();
      if (!text) return;
      input.value = '';
      // Optimistic render with a temp id so user sees their message immediately.
      var tempId = 'tmp_' + Date.now();
      renderMessage({ id: tempId, direction: 'inbound', content: text, created_at: new Date().toISOString(), created_at_ms: Date.now() }, true);
      sendMessage(text).catch(function () {
        // Mark optimistic message as failed.
        var bubbleNode = document.querySelector('[data-msg-id="' + tempId + '"]');
        if (bubbleNode) bubbleNode.style.opacity = '0.5';
      });
    }
  }

  function togglePanel() {
    panelOpen = !panelOpen;
    ui.panel.style.display = panelOpen ? 'flex' : 'none';
    ui.bubble.style.display = panelOpen ? 'none' : 'flex';
    if (panelOpen) {
      setPollInterval(1500);
      ui.input.focus();
      // One immediate poll so the user sees fresh agent replies right away.
      poll();
    } else {
      setPollInterval(3000);
    }
  }

  function renderMessage(m, scroll) {
    if (!ui.thread) return;
    if (m.id && renderedIds[m.id]) return;
    if (m.id) renderedIds[m.id] = true;
    if (m.created_at_ms && m.created_at_ms > lastSeenMs) lastSeenMs = m.created_at_ms;

    var isInbound = m.direction === 'inbound';
    var row = el('div', {
      'data-msg-id': m.id,
      style: {
        display: 'flex', justifyContent: isInbound ? 'flex-end' : 'flex-start'
      }
    });
    var bubbleNode = el('div', {
      style: {
        maxWidth: '78%', padding: '8px 12px', borderRadius: '14px',
        fontSize: '14px', lineHeight: '1.4', wordBreak: 'break-word',
        background: isInbound ? config.theme_color : 'white',
        color: isInbound ? 'white' : '#111827',
        border: isInbound ? 'none' : '1px solid #e5e7eb',
        boxShadow: isInbound ? 'none' : '0 1px 1px rgba(0,0,0,0.03)'
      },
      textContent: m.content || ''
    });
    var timeNode = el('div', {
      style: {
        fontSize: '10px', color: '#9ca3af',
        marginTop: '2px', textAlign: isInbound ? 'right' : 'left'
      },
      textContent: fmtTime(m.created_at)
    });
    var col = el('div', { style: { display: 'flex', flexDirection: 'column', maxWidth: '100%' } }, [bubbleNode, timeNode]);
    row.appendChild(col);
    ui.thread.appendChild(row);
    if (scroll) ui.thread.scrollTop = ui.thread.scrollHeight;
  }

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------
  function init() {
    if (!apiBase) {
      console.warn('[OneInbox] widget.js: could not derive API base from script src');
      return;
    }
    buildUI();
    ensureVisitor().then(function () {
      startPolling();
    }).catch(function (e) {
      console.warn('[OneInbox] widget.js: init failed', e && e.message);
      // Hide bubble silently on hard failure (e.g. widget deleted).
      if (e.status === 404 && ui.root) ui.root.style.display = 'none';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
