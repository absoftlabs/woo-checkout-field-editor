(function() {
	'use strict';

	function byId(id) { return document.getElementById(id); }

	function setOptions(select, options, placeholder) {
		while (select.firstChild) select.removeChild(select.firstChild);
		var ph = document.createElement('option');
		ph.value = '';
		ph.textContent = placeholder || '';
		select.appendChild(ph);
		(options || []).forEach(function(opt) {
			var o = document.createElement('option');
			o.value = opt;
			o.textContent = opt;
			select.appendChild(o);
		});
	}

	function countryMatches(placement) {
		try {
			if (ABB_WCFE_BD.country_mode !== 'BD_only') return true;
			var countrySel = document.getElementById(placement + '_country');
			if (!countrySel) return true; // fail open
			return (countrySel.value === 'BD');
		} catch(e){ return true; }
	}

	function toggleBDVisibility(placement, show) {
		['bd_district','bd_subdistrict'].forEach(function(suffix){
			var el = document.getElementById(placement + '_' + suffix);
			if (el && el.closest('.form-row')) {
				el.closest('.form-row').style.display = show ? '' : 'none';
			}
		});
	}

	function handle(placement) {
		var dId = placement + '_bd_district';
		var uId = placement + '_bd_subdistrict';
		var d = byId(dId);
		var u = byId(uId);
		if (!d || !u) return;

		// Country condition toggle
		var applyCountry = function(){
			var visible = countryMatches(placement);
			toggleBDVisibility(placement, visible);
		};
		applyCountry();
		var countrySel = document.getElementById(placement + '_country');
		if (countrySel) countrySel.addEventListener('change', applyCountry);

		// Initial populate if district already set
		var currentDistrict = d.value || '';
		if (currentDistrict && ABB_WCFE_BD.geo[currentDistrict]) {
			setOptions(u, ABB_WCFE_BD.geo[currentDistrict], ABB_WCFE_BD.i18n.select_subdistrict);
			if (u.dataset.initialValue) u.value = u.dataset.initialValue;
		} else {
			setOptions(u, [], ABB_WCFE_BD.i18n.select_subdistrict);
		}

		d.addEventListener('change', function() {
			var district = d.value;
			var list = ABB_WCFE_BD.geo[district] || [];
			setOptions(u, list, ABB_WCFE_BD.i18n.select_subdistrict);
		});
	}

	document.addEventListener('DOMContentLoaded', function() {
		try {
			handle('billing');
			handle('shipping');
		} catch (e) { /* noop */ }
	});
})();
