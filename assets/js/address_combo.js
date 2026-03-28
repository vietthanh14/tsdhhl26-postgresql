/**
 * Address Combo Module — assets/js/address_combo.js
 * Hệ thống Combobox chọn Tỉnh/Thành phố → Phường/Xã
 * Dùng chung cho: profile.php, admin/users.php
 *
 * Cách dùng:
 *   AddressCombo.init(apiBase, prefix, { hiddenProvince, hiddenWard })
 *   AddressCombo.restore(prefix, savedProvince, savedWard)
 */
window.AddressCombo = (function () {
    'use strict';

    let provincesCache = null;

    function closeAll() {
        document.querySelectorAll('.combo-dropdown.open').forEach(d => d.classList.remove('open'));
    }

    // Global click-outside handler (registered once)
    let _globalListenerAdded = false;
    function ensureGlobalListener() {
        if (_globalListenerAdded) return;
        document.addEventListener('click', e => {
            if (!e.target.closest('.combo-wrapper')) closeAll();
        });
        _globalListenerAdded = true;
    }

    function makeCombo({ triggerEl, dropdown, searchEl, listEl, clearEl, onClear }) {
        triggerEl.addEventListener('click', () => {
            if (triggerEl.disabled) return;
            const isOpen = dropdown.classList.contains('open');
            closeAll();
            if (!isOpen) {
                dropdown.classList.add('open');
                searchEl.value = '';
                searchEl.dispatchEvent(new Event('input'));
                setTimeout(() => searchEl.focus(), 50);
            }
        });
        searchEl.addEventListener('input', () => {
            const q = searchEl.value.toLowerCase().trim();
            listEl.querySelectorAll('.combo-option').forEach(opt => {
                opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
            const visible = [...listEl.querySelectorAll('.combo-option')].filter(o => o.style.display !== 'none');
            let noRes = listEl.querySelector('.no-result');
            if (!visible.length) {
                if (!noRes) {
                    noRes = document.createElement('div');
                    noRes.className = 'combo-option no-result';
                    noRes.textContent = 'Không tìm thấy kết quả';
                    listEl.appendChild(noRes);
                }
            } else { if (noRes) noRes.remove(); }
        });
        if (clearEl && onClear) clearEl.addEventListener('click', onClear);
    }

    function renderOptions(listEl, items, onSelect) {
        listEl.innerHTML = '';
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'combo-option';
            div.textContent = item.name;
            div.dataset.code = item.code;
            div.addEventListener('click', () => { onSelect(item); closeAll(); });
            listEl.appendChild(div);
        });
    }

    function loadProvinces(apiBase) {
        if (provincesCache) return Promise.resolve(provincesCache);
        return fetch(apiBase + '?action=provinces')
            .then(r => r.json())
            .then(data => { provincesCache = data; return data; });
    }

    /**
     * Init an address section (Tỉnh + Phường).
     * @param {string} apiBase — URL of dia_danh.php
     * @param {string} prefix — ID prefix, e.g. 'province' or 'editSchoolProvince'
     * @param {object} opts — { hiddenProvince, hiddenWard, wardDisabledByDefault }
     *   hiddenProvince / hiddenWard: ID of <input type="hidden"> that stores the name value
     *
     * Expected DOM IDs (with prefix):
     *   {prefix}Input, {prefix}Dropdown, {prefix}Search, {prefix}List, {prefix}Clear
     *   and for ward: replace first letter of 'Province' with 'Ward' in prefix pattern
     *
     * For convenience, this function also accepts explicit element ID config:
     */
    function init(apiBase, cfg) {
        ensureGlobalListener();

        const pInput    = document.getElementById(cfg.provinceInputId);
        const pDropdown = document.getElementById(cfg.provinceDropdownId);
        const pSearch   = document.getElementById(cfg.provinceSearchId);
        const pList     = document.getElementById(cfg.provinceListId);
        const pHidden   = document.getElementById(cfg.provinceHiddenId);
        const pClear    = document.getElementById(cfg.provinceClearId);

        const wInput    = document.getElementById(cfg.wardInputId);
        const wDropdown = document.getElementById(cfg.wardDropdownId);
        const wSearch   = document.getElementById(cfg.wardSearchId);
        const wList     = document.getElementById(cfg.wardListId);
        const wHidden   = document.getElementById(cfg.wardHiddenId);
        const wClear    = document.getElementById(cfg.wardClearId);

        const addrDetail = cfg.addressDetailId ? document.getElementById(cfg.addressDetailId) : null;

        function clearWard()     { wInput.value = ''; wHidden.value = ''; wClear.style.display = 'none'; }
        function clearProvince() { pInput.value = ''; pHidden.value = ''; pClear.style.display = 'none'; clearWard(); wInput.disabled = true; }

        function selectProvince(p, skipWardReset) {
            pInput.value = p.name; pHidden.value = p.name; pClear.style.display = 'block';
            if (!skipWardReset) { clearWard(); wInput.disabled = false; }
            fetch(`${apiBase}?action=wards&province_code=${encodeURIComponent(p.code)}`)
                .then(r => r.json())
                .then(wards => renderOptions(wList, wards, selectWard));
        }

        function selectWard(w) {
            wInput.value = w.name; wHidden.value = w.name; wClear.style.display = 'block';
        }

        makeCombo({ triggerEl: pInput, dropdown: pDropdown, searchEl: pSearch, listEl: pList, clearEl: pClear, onClear: clearProvince });
        makeCombo({ triggerEl: wInput, dropdown: wDropdown, searchEl: wSearch, listEl: wList, clearEl: wClear, onClear: clearWard });

        // Load provinces and restore saved values
        loadProvinces(apiBase).then(provinces => {
            renderOptions(pList, provinces, p => selectProvince(p));
            if (addrDetail && cfg.savedAddressDetail) addrDetail.value = cfg.savedAddressDetail;
            if (cfg.savedProvince) {
                const match = provinces.find(p => p.name === cfg.savedProvince);
                if (match) {
                    selectProvince(match, true);
                    if (cfg.savedWard) {
                        fetch(`${apiBase}?action=wards&province_code=${encodeURIComponent(match.code)}`)
                            .then(r => r.json())
                            .then(wards => {
                                renderOptions(wList, wards, selectWard);
                                wInput.disabled = false;
                                const mw = wards.find(w => w.name === cfg.savedWard);
                                if (mw) selectWard(mw);
                            });
                    }
                }
            }
        }).catch(() => console.warn('Không thể tải dữ liệu địa danh.'));

        // Store references for external restore calls
        const key = cfg.comboKey || cfg.provinceInputId;
        _combos[key] = { selectProvince, selectWard, clearProvince, loadProvinces: () => loadProvinces(apiBase) };
    }

    const _combos = {};

    /**
     * Restore saved values in a previously initialized combo.
     * @param {string} key — the comboKey used during init
     */
    function restore(key, savedProvince, savedWard, apiBase) {
        const combo = _combos[key];
        if (!combo) return;
        combo.clearProvince();
        if (!savedProvince) return;

        combo.loadProvinces().then(provinces => {
            const match = provinces.find(p => p.name === savedProvince);
            if (match) {
                combo.selectProvince(match, true);
                if (savedWard) {
                    fetch(`${apiBase}?action=wards&province_code=${encodeURIComponent(match.code)}`)
                        .then(r => r.json())
                        .then(wards => {
                            // Find the ward list element
                            const wListId = key.replace('Input', 'List').replace('Province', 'Ward');
                            const wList = document.getElementById(wListId) || document.getElementById(key.replace('ProvinceInput', 'WardList'));
                            if (wList) renderOptions(wList, wards, combo.selectWard);
                            const wInput = document.getElementById(key.replace('Province', 'Ward'));
                            if (wInput) wInput.disabled = false;
                            const mw = wards.find(w => w.name === savedWard);
                            if (mw) combo.selectWard(mw);
                        });
                }
            }
        });
    }

    return { init, restore, closeAll };
})();
