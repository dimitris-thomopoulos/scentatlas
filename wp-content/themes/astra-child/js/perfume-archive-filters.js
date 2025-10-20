(function(){
	const $ = (sel, ctx=document) => ctx.querySelector(sel);
	const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

	const state = {
		q: '',
		top_note: [],
		middle_note: [],
		base_note: [],
		concentration: [],
		fragrance_family: [],
		countries: [],
		sort_order: 'title_asc', // ✅ default
		year_min: null,
		year_max: null,
		page: 1,
		per_page: PF_API.perPage || 12,
	};

	const els = {
		grid: $('#pf-grid'),
		count: $('#pf-count'),
		pager: $('#pf-pagination'),
		search: $('#pf-search'),
		clear: $('#pf-clear'),
		ddHolders: $$('.pf-dd'),
		yearMin: $('#pf-year-min'),
		yearMax: $('#pf-year-max'),
		yearMinLabel: $('#pf-year-min-label'),
		yearMaxLabel: $('#pf-year-max-label'),
	};

// ---------- Helper: render selected chips inside dropdown button ----------
function renderChips(holder, key, label) {
  const btn = holder.querySelector('.pf-dd-btn');
  const selections = state[key];

  if (!holder._expanded) holder._expanded = false;
  const expanded = holder._expanded;

  if (!selections.length) {
    btn.innerHTML = label;
    return;
  }

  btn.innerHTML = '';
  const labelSpan = document.createElement('span');
  labelSpan.textContent = label + ': ';
//  btn.appendChild(labelSpan);

  let visible = selections;
  let hiddenCount = 0;
  if (!expanded && selections.length > 4) {
    visible = selections.slice(0, 2);
    hiddenCount = selections.length - visible.length;
  }

  visible.forEach(val => {
    // CHIP CONTAINER — no overflow hidden here
    const chip = document.createElement('span');
    chip.style.cssText = `
      display:inline-grid;
      grid-auto-flow: column;
      grid-template-columns: 1fr auto; /* text | close */
      align-items:center;
      background:#f3f3f3;
      border-radius:6px;
      padding:2px 6px;
      margin-left:4px;
      font-size:.85em;
      max-width:120px; /* controls overall chip width */
      vertical-align:middle;
    `;

    // TEXT SPAN — applies truncation/ellipsis ONLY here
    const textSpan = document.createElement('span');
    textSpan.textContent = val;
    textSpan.style.cssText = `
      min-width:0;           /* allow shrinking in grid/flex */
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
      line-height:1.2;
    `;
    chip.appendChild(textSpan);

    // CLOSE ICON — its own cell, never truncated
    const x = document.createElement('button');
    x.type = 'button';
    x.setAttribute('aria-label', `Remove ${val}`);
    x.textContent = '✕';
    x.style.cssText = `
      margin-left:6px;
      cursor:pointer;
      color:#666;
      border:0;
      background:transparent;
      padding:0;
      line-height:1;
      flex:0 0 auto;
    `;
    x.addEventListener('click', e => {
      e.stopPropagation(); // don't toggle dropdown
      state[key] = state[key].filter(v => v !== val);
      // uncheck matching checkbox if present
      const cb = document.querySelector(`.pf-dd[data-key="${key}"] .pf-dd-panel input[type=checkbox][value="${CSS.escape(val)}"]`);
      if (cb) cb.checked = false;
      state.page = 1;
      renderChips(holder, key, label);
      runSearch();
    });
    chip.appendChild(x);
    chip.classList.add(`chip-${key}`);

    btn.appendChild(chip);
  });

  if (!expanded && hiddenCount > 0) {
    const more = document.createElement('span');
    more.textContent = `+${hiddenCount} more`;
    more.style.cssText = 'margin-left:4px;font-size:.85em;color:#666;cursor:pointer;user-select:none;';
    more.addEventListener('click', e => {
      e.stopPropagation();
      holder._expanded = true;
      renderChips(holder, key, label);
    });
    btn.appendChild(more);
  }

  if (expanded && selections.length > 2) {
    const less = document.createElement('span');
    less.textContent = '▲';
    less.title = 'Collapse';
    less.style.cssText = 'margin-left:4px;font-size:.85em;color:#666;cursor:pointer;user-select:none;';
    less.addEventListener('click', e => {
      e.stopPropagation();
      holder._expanded = false;
      renderChips(holder, key, label);
    });
    btn.appendChild(less);
  }
}




// ---------- UI: dropdown with checkboxes ----------
function buildDropdown(holder, label, key, options, isSort=false) {
	holder.innerHTML = '';
	holder.classList.add('pf-dd-wrap');
	holder.style.position = 'relative';

	const btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'pf-dd-btn';
	btn.textContent = label;
	btn.style.cssText = `
		padding:.5rem .8rem;
		border:1px solid #ddd;
		border-radius:8px;
		background:#fff;
		cursor:pointer;
		display:flex;
		flex-wrap:wrap;
		gap:4px;
		align-items:center;
		max-width:260px;
		overflow:hidden;
		text-overflow:ellipsis;
	`;
	holder.appendChild(btn);

	const panel = document.createElement('div');
	panel.className = 'pf-dd-panel';
	panel.style.cssText = `
		position:absolute;z-index:50;
		background:#fff;border:1px solid #eee;
		border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.08);
		padding:.5rem;min-width:220px;max-height:280px;
		overflow:auto;display:none;margin-top:6px;
	`;
	holder.appendChild(panel);

	btn.addEventListener('click', () => {
		const visible = panel.style.display === 'block';
		$$('.pf-dd-panel').forEach(p => p.style.display='none');
		panel.style.display = visible ? 'none' : 'block';
	});
	document.addEventListener('click', (e) => {
		if (!holder.contains(e.target)) panel.style.display='none';
	});

	if (isSort) {
		options.forEach(opt => {
			const item = document.createElement('div');
			item.textContent = opt.label;
			item.style.cssText = 'padding:.4rem .3rem;cursor:pointer';
			item.addEventListener('click', () => {
				state.sort_order = opt.value;
				panel.style.display='none';
				btn.textContent = `${label}: ${opt.label}`;
				state.page = 1;
				runSearch();
			});
			panel.appendChild(item);
		});
		return;
	}

	const filterBox = document.createElement('input');
	filterBox.type = 'search';
	filterBox.placeholder = 'Filter…';
	filterBox.style.cssText = 'width:100%;padding:.4rem;border:1px solid #eee;border-radius:6px;margin-bottom:.4rem';
	panel.appendChild(filterBox);

	const list = document.createElement('div');
	panel.appendChild(list);

	function renderList() {
		const term = filterBox.value.trim().toLowerCase();
		list.innerHTML = '';
		options
			.filter(o => o.toLowerCase().includes(term))
			.forEach(opt => {
				const id = `pf-${key}-${opt.replace(/\s+/g,'-').toLowerCase()}`;
				const wrap = document.createElement('label');
				wrap.style.cssText = 'display:flex;align-items:center;gap:8px;padding:.25rem .2rem;cursor:pointer';
				wrap.setAttribute('for', id);

				const cb = document.createElement('input');
				cb.type='checkbox';
				cb.id=id;
				cb.value=opt;
				cb.checked = state[key].includes(opt);
				cb.addEventListener('change', () => {
					if (cb.checked) {
						if (!state[key].includes(opt)) state[key].push(opt);
					} else {
						state[key] = state[key].filter(v => v!==opt);
					}
					state.page = 1;
					renderChips(holder, key, label); // refresh visible chips
					queueSearch();
				});

				const text = document.createElement('span');
				text.textContent = opt;
				wrap.appendChild(cb);
				wrap.appendChild(text);
				list.appendChild(wrap);
			});
	}
	filterBox.addEventListener('input', renderList);
	renderList();

	renderChips(holder, key, label); // initial render
}


	let t=null;
	function debounce(fn, ms){ return function(){ clearTimeout(t); t=setTimeout(fn, ms);} }
	const queueSearch = debounce(runSearch, 150);

	async function init() {
		const r = await fetch(PF_API.root + 'facets', { headers:{'X-WP-Nonce': PF_API.nonce}});
		const data = await r.json();

		const ymin = data?.year?.min ?? 1900;
		const ymax = data?.year?.max ?? (new Date()).getFullYear();
		state.year_min = ymin;
		state.year_max = ymax;

		els.yearMin.min = ymin; els.yearMin.max = ymax; els.yearMin.value = ymin;
		els.yearMax.min = ymin; els.yearMax.max = ymax; els.yearMax.value = ymax;
		els.yearMinLabel.textContent = ymin;
		els.yearMaxLabel.textContent = ymax;

		const labels = {
			top_note: 'Top Notes',
			middle_note: 'Middle Notes',
			base_note: 'Base Notes',
			concentration: 'Concentration',
			countries: 'Location',
			fragrance_family: 'Fragrance Family',
		};
                
                

		els.ddHolders.forEach(holder => {
			const key = holder.getAttribute('data-key');
			if (key === 'sort_order') { // ✅ new
				buildDropdown(holder, 'Sort', key, [
					{label:'A → Z', value:'title_asc'},
					{label:'Z → A', value:'title_desc'},
					{label:'Year ↑ (old → new)', value:'year_asc'},
					{label:'Year ↓ (new → old)', value:'year_desc'},
				], true);
			} else {
				buildDropdown(holder, labels[key], key, data.facets[key] || []);
			}
		});

		els.search.addEventListener('input', () => {
			state.q = els.search.value.trim();
			state.page = 1;
			queueSearch();
		});

		function clampYears(){
			let min = parseInt(els.yearMin.value,10);
			let max = parseInt(els.yearMax.value,10);
			if (min > max) [min, max] = [max, min];
			els.yearMinLabel.textContent = String(min);
			els.yearMaxLabel.textContent = String(max);
			state.year_min = min; state.year_max = max;
		}
		const onYearChange = () => { clampYears(); state.page=1; queueSearch(); };
		els.yearMin.addEventListener('input', onYearChange);
		els.yearMax.addEventListener('input', onYearChange);

		els.clear.addEventListener('click', () => {
	// 1️⃣ Reset all state
	state.q = '';
	state.sort_order = 'title_asc';
	els.search.value = '';
	['top_note','middle_note','base_note','concentration','fragrance_family','countries'].forEach(k => state[k] = []);
	els.yearMin.value = ymin;
	els.yearMax.value = ymax;
	els.yearMinLabel.textContent = ymin;
	els.yearMaxLabel.textContent = ymax;
	state.year_min = ymin;
	state.year_max = ymax;
	state.page = 1;

	// 2️⃣ Uncheck all checkboxes
	$$('.pf-dd-panel input[type=checkbox]').forEach(cb => cb.checked = false);

	// ✅ Reset each filter button label (clear innerHTML chips)
	els.ddHolders.forEach(holder => {
		const key = holder.getAttribute('data-key');
		if (key && key !== 'sort_order') {
			const label = holder.querySelector('.pf-dd-btn')?.textContent?.split(':')[0] || key;
			const properLabel = {
				top_note: 'Top Notes',
				middle_note: 'Middle Notes',
				base_note: 'Base Notes',
				concentration: 'Concentration',
				fragrance_family: 'Fragrance Family',
				countries: 'Location',
			}[key] || label;
			renderChips(holder, key, properLabel);
		}
		// Reset Sort dropdown label
		if (key === 'sort_order') {
			const btn = holder.querySelector('.pf-dd-btn');
			if (btn) btn.textContent = 'Sort by: A → Z';
		}
	});
        

	// 5️⃣ Re-run search and facet counts
	runSearch();
});



		runSearch();
	}

	function qs() {
		const p = new URLSearchParams();
		p.set('per_page', state.per_page);
		p.set('page', state.page);
		if (state.q) p.set('q', state.q);
		if (state.year_min) p.set('year_min', state.year_min);
		if (state.year_max) p.set('year_max', state.year_max);
		if (state.sort_order) p.set('sort', state.sort_order); // ✅ new
		for (const k of ['top_note','middle_note','base_note','concentration','fragrance_family','countries']) {
			if (state[k].length) p.set(k, state[k].join(','));
		}
		return p.toString();
	}

	function renderPager(page, pages) {
		els.pager.innerHTML = '';
		if (pages < 2) return;
		function addBtn(txt, target, disabled=false, current=false){
			const b = document.createElement('button');
			b.type='button';
			b.textContent = txt;
			b.style.cssText = `padding:.4rem .7rem;border:1px solid #ddd;border-radius:6px;background:${current?'#000':'#fff'};color:${current?'#fff':'#000'};cursor:${disabled?'not-allowed':'pointer'};`;
			b.disabled = disabled;
			if (!disabled && !current) b.addEventListener('click', () => { state.page = target; runSearch(); });
			els.pager.appendChild(b);
		}
		addBtn('« Prev', Math.max(1, page-1), page===1);
		const start = Math.max(1, page-2);
		const end   = Math.min(pages, page+2);
		for (let i=start;i<=end;i++){ addBtn(String(i), i, false, i===page); }
		addBtn('Next »', Math.min(pages, page+1), page===pages);
	}

	let inflight = 0;
	async function runSearch() {
	inflight++;
	const myTurn = inflight;
	els.grid.style.opacity = '.6';

	const baseParams = qs();

	// === 1. Fetch main results ===
	const r = await fetch(PF_API.root + 'search?' + baseParams, { headers:{'X-WP-Nonce': PF_API.nonce}});
	const data = await r.json();
	if (myTurn !== inflight) return;

	els.grid.innerHTML = data.html || '';
	els.count.textContent = `${data.total || 0} result${(data.total||0)===1?'':'s'}`;
	renderPager(data.page||1, data.pages||1);
	els.grid.style.opacity = '1';

	// === 2. Fetch live facet counts ===
	const rf = await fetch(PF_API.root + 'facets_live?' + baseParams, { headers:{'X-WP-Nonce': PF_API.nonce}});
	const fdata = await rf.json();
	if (!fdata || !fdata.counts) return;

	// === 3. Update counts in dropdowns ===
	for (const [key, map] of Object.entries(fdata.counts)) {
		const holder = document.querySelector(`.pf-dd[data-key="${key}"]`);
		if (!holder) continue;

		const panel = holder.querySelector('.pf-dd-panel');
		if (!panel) continue;

		// Find all checkboxes inside
		const labels = panel.querySelectorAll('label');
		labels.forEach(labelEl => {
			const cb = labelEl.querySelector('input');
			if (!cb) return;
			const val = cb.value;
			const count = map[val] ?? 0;

			// Remove previous count
			const old = labelEl.querySelector('.pf-count');
			if (old) old.remove();

			if (count > 0) {
				const span = document.createElement('span');
				span.className = 'pf-count';
				span.textContent = ` (${count})`;
				span.style.cssText = 'color:#666;font-size:.85em;';
				labelEl.appendChild(span);
				labelEl.style.display = '';
			} else {
				// Hide if 0 results and not currently checked
				if (!cb.checked) labelEl.style.display = 'none';
				else labelEl.style.display = '';
			}
		});
	}
}


	document.addEventListener('DOMContentLoaded', init);
})();
