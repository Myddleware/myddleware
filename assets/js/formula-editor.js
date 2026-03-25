const BRACKET_PAIRS = {
  '(': ')', ')': '(',
  '[': ']', ']': '[',
  '{': '}', '}': '{',
};
const OPENERS = new Set(['(', '[', '{']);
const CLOSERS = new Set([')', ']', '}']);

const QUOTE_CHARS = new Set(["'", '"', '`']);

const BRACKET_COLORS = [
  '#e6b422',
  '#d946ef',
  '#22d3ee',
  '#f97316',
  '#a3e635',
  '#f43f5e',
  '#818cf8',
  '#2dd4bf',
  '#fb923c',
  '#c084fc',
  '#34d399',
  '#f87171',
];

const INDENT_COLORS = [
  'rgba(33, 180, 226, 0.89)',
  'rgba(225, 220, 220, 0.85)',
];

const DEFAULT_TEXT_COLOR = '#374151';

function tokenize(text) {
  const tokens = []; // one per character
  const bracketStacks = { '(': [], '[': [], '{': [] };
  const quoteState = { "'": -1, '"': -1, '`': -1 };
  const quoteCounts = { "'": 0, '"': 0, '`': 0 };
  let activeQuote = null; // which quote char we are currently inside, or null

  for (let i = 0; i < text.length; i++) {
    const ch = text[i];

    if (activeQuote !== null) {
      if (ch === activeQuote) {
        // Closing quote
        const openIdx = quoteState[ch];
        const depth = quoteCounts[ch]++;
        tokens[openIdx] = { type: 'quote', index: openIdx, matchIndex: i, depth, char: ch };
        tokens[i] = { type: 'quote', index: i, matchIndex: openIdx, depth, char: ch };
        activeQuote = null;
      } else {
        tokens[i] = { type: 'text', index: i, char: ch };
      }
      continue;
    }

    if (QUOTE_CHARS.has(ch)) {
      quoteState[ch] = i;
      activeQuote = ch;
      tokens[i] = null; // placeholder, filled when matched
      continue;
    }

    if (OPENERS.has(ch)) {
      bracketStacks[ch].push(i);
      tokens[i] = null; // placeholder
      continue;
    }

    if (CLOSERS.has(ch)) {
      const opener = BRACKET_PAIRS[ch];
      const stack = bracketStacks[opener];
      if (stack.length > 0) {
        const openIdx = stack.pop();
        const depth = stack.length;
        tokens[openIdx] = { type: 'bracket', index: openIdx, matchIndex: i, depth, char: opener };
        tokens[i] = { type: 'bracket', index: i, matchIndex: openIdx, depth, char: opener };
      } else {
        tokens[i] = { type: 'bracket', index: i, matchIndex: -1, depth: 0, char: opener };
      }
      continue;
    }

    tokens[i] = { type: 'text', index: i, char: ch };
  }

  for (const type of Object.keys(bracketStacks)) {
    for (const idx of bracketStacks[type]) {
      if (!tokens[idx]) {
        tokens[idx] = { type: 'bracket', index: idx, matchIndex: -1, depth: 0, char: type };
      }
    }
  }

  if (activeQuote !== null) {
    const idx = quoteState[activeQuote];
    if (!tokens[idx]) {
      tokens[idx] = { type: 'quote', index: idx, matchIndex: -1, depth: 0, char: activeQuote };
    }
  }

  for (let i = 0; i < text.length; i++) {
    if (!tokens[i]) {
      tokens[i] = { type: 'text', index: i, char: text[i] };
    }
  }

  return tokens;
}

function escapeHTML(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function buildHighlightedHTML(text, options = {}) {
  const { rainbowBrackets = true, indentRainbow = true, highlightPos = -1 } = options;

  if (!text) return '&nbsp;';

  const tokens = tokenize(text);

  let highlightA = -1, highlightB = -1;
  if (highlightPos >= 0) {
    for (const pos of [highlightPos, highlightPos - 1]) {
      if (pos >= 0 && pos < tokens.length) {
        const tok = tokens[pos];
        if ((tok.type === 'bracket' || tok.type === 'quote') && tok.matchIndex >= 0) {
          highlightA = pos;
          highlightB = tok.matchIndex;
          break;
        }
      }
    }
  }

  const lines = text.split('\n');
  const htmlLines = [];
  let gi = 0; // global index

  for (let ln = 0; ln < lines.length; ln++) {
    const line = lines[ln];
    let html = '';

    let indentEnd = 0;
    if (indentRainbow) {
      while (indentEnd < line.length && (line[indentEnd] === ' ' || line[indentEnd] === '\t')) {
        indentEnd++;
      }
      if (indentEnd > 0) {
        let level = 0, pos = 0;
        while (pos < indentEnd) {
          let end;
          if (line[pos] === '\t') {
            end = pos + 1;
          } else {
            end = Math.min(pos + 2, indentEnd);
          }
          const bg = INDENT_COLORS[level % INDENT_COLORS.length];
          html += `<span style="background:${bg}">${escapeHTML(line.substring(pos, end))}</span>`;
          gi += (end - pos);
          pos = end;
          level++;
        }
      }
    }

    for (let ci = indentEnd; ci < line.length; ci++) {
      const tok = tokens[gi];
      const escaped = escapeHTML(line[ci]);

      if (tok && (tok.type === 'bracket' || tok.type === 'quote')) {
        const isHL = (gi === highlightA || gi === highlightB);
        const hlCls = isHL ? ' fe-bracket-highlight' : '';

        if (rainbowBrackets) {
          if (tok.matchIndex === -1) {
            html += `<span class="fe-bracket-unmatched${hlCls}">${escaped}</span>`;
          } else if (tok.type === 'bracket') {
            const color = BRACKET_COLORS[tok.depth % BRACKET_COLORS.length];
            html += `<span class="fe-bracket${hlCls}" style="color:${color}">${escaped}</span>`;
          } else {
            const color = BRACKET_COLORS[(tok.depth + 6) % BRACKET_COLORS.length];
            html += `<span class="fe-quote${hlCls}" style="color:${color}">${escaped}</span>`;
          }
        } else if (isHL) {
          html += `<span class="fe-bracket-highlight" style="color:${DEFAULT_TEXT_COLOR}">${escaped}</span>`;
        } else {
          html += `<span style="color:${DEFAULT_TEXT_COLOR}">${escaped}</span>`;
        }
      } else {
        html += `<span style="color:${DEFAULT_TEXT_COLOR}">${escaped}</span>`;
      }
      gi++;
    }

    htmlLines.push(html || '&nbsp;');
    gi++; // newline
  }

  return htmlLines.join('\n');
}

function initFormulaEditor(textarea) {
  if (!textarea || textarea.dataset.feInitialized) return null;
  textarea.dataset.feInitialized = '1';

  const wrapper = document.createElement('div');
  wrapper.className = 'fe-wrapper';

  const highlight = document.createElement('pre');
  highlight.className = 'fe-highlight';
  highlight.setAttribute('aria-hidden', 'true');

  textarea.parentNode.insertBefore(wrapper, textarea);
  wrapper.appendChild(highlight);
  wrapper.appendChild(textarea);
  textarea.classList.add('fe-textarea');

  let rainbowBrackets = false;
  let indentRainbow = false;

  function update() {
    const text = textarea.value;
    const cursorPos = textarea.selectionStart;
    highlight.innerHTML = buildHighlightedHTML(text, {
      rainbowBrackets,
      indentRainbow,
      highlightPos: document.activeElement === textarea ? cursorPos : -1,
    });
    syncScroll();
  }

  function syncScroll() {
    highlight.scrollTop = textarea.scrollTop;
    highlight.scrollLeft = textarea.scrollLeft;
  }

  textarea.addEventListener('keydown', function(e) {
    if (e.key === 'Tab' && !e.shiftKey) {
      e.preventDefault();
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      textarea.value = textarea.value.substring(0, start) + '\t' + textarea.value.substring(end);
      textarea.selectionStart = textarea.selectionEnd = start + 1;
      update();
    } else if (e.key === 'Tab' && e.shiftKey) {
      e.preventDefault();
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      // Remove one tab or up to 4 spaces before the cursor
      const before = textarea.value.substring(0, start);
      if (before.endsWith('\t')) {
        textarea.value = before.slice(0, -1) + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start - 1;
      } else {
        // Remove up to 4 trailing spaces
        const match = before.match(/ {1,4}$/);
        if (match) {
          const count = match[0].length;
          textarea.value = before.slice(0, -count) + textarea.value.substring(end);
          textarea.selectionStart = textarea.selectionEnd = start - count;
        }
      }
      update();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      // Find the current line's leading whitespace
      const textBefore = textarea.value.substring(0, start);
      const lineStart = textBefore.lastIndexOf('\n') + 1;
      const currentLine = textBefore.substring(lineStart);
      const indent = currentLine.match(/^[ \t]*/)[0];
      // Insert newline + same indentation
      const insertion = '\n' + indent;
      textarea.value = textarea.value.substring(0, start) + insertion + textarea.value.substring(end);
      textarea.selectionStart = textarea.selectionEnd = start + insertion.length;
      update();
    }
  });

  textarea.addEventListener('input', update);
  textarea.addEventListener('keyup', update);
  textarea.addEventListener('click', update);
  textarea.addEventListener('focus', update);
  textarea.addEventListener('blur', update);
  textarea.addEventListener('scroll', syncScroll);

  update();

  // Toggle toolbar
  const toolbar = document.createElement('div');
  toolbar.className = 'fe-toolbar';

  function makeToggle(icon, label, initial, onChange) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'fe-toggle' + (initial ? ' active' : '');
    btn.innerHTML = `<i class="fas fa-${icon}"></i> ${label}`;
    btn.addEventListener('click', () => {
      const val = onChange();
      btn.classList.toggle('active', val);
      update();
    });
    return btn;
  }

  const btnBrackets = makeToggle('code', 'Rainbow brackets', false, () => (rainbowBrackets = !rainbowBrackets));
  const btnIndent = makeToggle('indent', 'Indent colors', false, () => (indentRainbow = !indentRainbow));
  toolbar.appendChild(btnBrackets);
  toolbar.appendChild(btnIndent);
  wrapper.parentNode.insertBefore(toolbar, wrapper);

  return {
    update,
    destroy() {
      textarea.removeEventListener('input', update);
      textarea.removeEventListener('keyup', update);
      textarea.removeEventListener('click', update);
      textarea.removeEventListener('focus', update);
      textarea.removeEventListener('blur', update);
      textarea.removeEventListener('scroll', syncScroll);
      wrapper.parentNode.insertBefore(textarea, wrapper);
      wrapper.remove();
      toolbar.remove();
      textarea.classList.remove('fe-textarea');
      delete textarea.dataset.feInitialized;
    }
  };
}

$(document).ready(function() {
  let editorInstance = null;

  $('#mapping-formula').on('shown.bs.modal', function() {
    const textarea = document.getElementById('area_insert');
    if (!textarea) return;

    if (!editorInstance) {
      editorInstance = initFormulaEditor(textarea);
    } else {
      editorInstance.update();
    }
  });

  $(document).on('click', '#formula-selected-fields .badge-formula, #submit-lookup, #insert-function-parameter', function() {
    setTimeout(() => { if (editorInstance) editorInstance.update(); }, 50);
  });

  window._feUpdate = function() {
    if (editorInstance) editorInstance.update();
  };
});

window.FormulaEditor = { initFormulaEditor, buildHighlightedHTML };
