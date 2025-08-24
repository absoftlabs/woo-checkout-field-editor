(function(){
	'use strict';

	// Tabs
	document.addEventListener('click', function(e){
		var btn = e.target.closest('.abbwcfe-tab');
		if (!btn) return;
		btn.parentElement.querySelectorAll('.abbwcfe-tab').forEach(function(t){ t.classList.remove('is-active'); });
		btn.classList.add('is-active');
		var target = btn.getAttribute('data-tab');
		document.querySelectorAll('.abbwcfe-panel').forEach(function(p){
			p.classList.toggle('is-active', p.getAttribute('data-panel') === target);
		});
	});

	// Per-panel search
	document.querySelectorAll('.abbwcfe-search').forEach(function(input){
		input.addEventListener('input', function(){
			var q = (input.value || '').toLowerCase();
			var scope = input.getAttribute('data-scope');
			document.querySelectorAll('.abbwcfe-panel[data-panel="'+scope+'"] .abbwcfe-card').forEach(function(card){
				var key = (card.getAttribute('data-key') || '').toLowerCase();
				var title = (card.querySelector('.card-head strong')?.textContent || '').toLowerCase();
				card.style.display = (key.indexOf(q) > -1 || title.indexOf(q) > -1) ? '' : 'none';
			});
		});
	});

	// Dim toggle (view-only) per scope
	document.querySelectorAll('.abbwcfe-opaque-toggle').forEach(function(toggle){
		toggle.addEventListener('change', function(){
			var scope = toggle.getAttribute('data-scope');
			var state = toggle.checked;
			document.querySelectorAll('.abbwcfe-panel[data-panel="'+scope+'"] .abbwcfe-card').forEach(function(card){
				card.style.opacity = state ? '0.85' : '1';
			});
		});
	});

	// ----- Drag & Drop on every sortable grid -----
	document.querySelectorAll('.abbwcfe-sortable').forEach(function(container){
		var dragging = null;

		container.addEventListener('dragstart', function(e){
			var card = e.target.closest('.abbwcfe-card');
			if (!card) return;
			dragging = card;
			card.classList.add('dragging');
			e.dataTransfer.effectAllowed = 'move';
			try { e.dataTransfer.setData('text/plain', card.dataset.key || ''); } catch(err){}
		});

		container.addEventListener('dragend', function(e){
			var card = e.target.closest('.abbwcfe-card');
			if (card) card.classList.remove('dragging');
			container.querySelectorAll('.abbwcfe-card.drop-target').forEach(function(el){ el.classList.remove('drop-target'); });
			dragging = null;
			updateBadgesAndPriorities(container);
		});

		container.addEventListener('dragover', function(e){
			e.preventDefault();
			if (!dragging) return;
			var after = getCardAfter(container, e.clientY);
			if (after == null) {
				container.appendChild(dragging);
			} else if (after !== dragging) {
				container.insertBefore(dragging, after);
			}
		});

		container.addEventListener('dragenter', function(e){
			var card = e.target.closest('.abbwcfe-card');
			if (!card || card === dragging) return;
			card.classList.add('drop-target');
		});
		container.addEventListener('dragleave', function(e){
			var card = e.target.closest('.abbwcfe-card');
			if (!card || card === dragging) return;
			card.classList.remove('drop-target');
		});

		function getCardAfter(container, y) {
			var cards = [].slice.call(container.querySelectorAll('.abbwcfe-card:not(.dragging)'));
			return cards.reduce(function(closest, child){
				var box = child.getBoundingClientRect();
				var offset = y - box.top - box.height / 2;
				if (offset < 0 && offset > closest.offset) {
					return { offset: offset, element: child };
				} else {
					return closest;
				}
			}, { offset: Number.NEGATIVE_INFINITY }).element;
		}

		function updateBadgesAndPriorities(container) {
			var step = parseInt(container.getAttribute('data-priority-step') || '10', 10);
			var n = 0;
			container.querySelectorAll('.abbwcfe-card').forEach(function(card){
				n += 1;
				var priority = n * step;
				var badge = card.querySelector('.badge');
				if (badge) badge.textContent = String(priority);
				var input = card.querySelector('input.js-priority');
				if (input) input.value = priority;
			});
		}

		// Normalize on load for each container
		updateBadgesAndPriorities(container);

		// Ensure priorities are synced before form submit
		var form = document.getElementById('abbwcfe-form');
		if (form) form.addEventListener('submit', function(){
			updateBadgesAndPriorities(container);
		});
	});
})();
