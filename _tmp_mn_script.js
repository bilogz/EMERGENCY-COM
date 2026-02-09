
        let templatesData = [];
        let categoriesData = [];
        let previewMode = 'sms';
        let mnWizardStep = 1;
        let mnDispatchFormHome = null;
        let mnPendingDispatchPayload = null;
        let mnMap = null;
        let mnMapMarker = null;
        let mnMapRadiusCircle = null;
        let mnMapTargetMode = 'location'; // 'location' | 'barangay'
        let mnMapSelected = { lat: null, lng: null, label: '', address: '' };
        let mnQcGeojson = null;
        let mnQcLayer = null;
        let mnQcBounds = null;
        let mnReverseGeocodeTimer = null;
        let mnReverseGeocodeSeq = 0;

        function openDispatchWizard() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const host = document.getElementById('mnWizardHost');
            const form = document.getElementById('dispatchForm');
            const home = document.getElementById('mnFormHost');

            if (!backdrop || !host || !form || !home) return;

            mnDispatchFormHome = home;
            host.appendChild(form);
            form.classList.add('mn-in-wizard');

            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ui-modal-open');

            mnWizardGoTo(1);
            setTimeout(() => document.getElementById('audienceType')?.focus(), 0);
        }

        function closeDispatchWizard() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const form = document.getElementById('dispatchForm');
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('ui-modal-open');

            if (form && mnDispatchFormHome) {
                form.classList.remove('mn-in-wizard');
                // restore all cards visible on page
                document.querySelectorAll('#dispatchForm .module-card').forEach(c => c.classList.remove('mn-step-active'));
                mnDispatchFormHome.appendChild(form);
            }
        }

        function mnWizardGoTo(step) {
            mnWizardStep = step;
            const form = document.getElementById('dispatchForm');
            if (!form) return;

            // show only selected card
            document.querySelectorAll('#dispatchForm .module-card').forEach(c => c.classList.remove('mn-step-active'));
            if (step === 1) document.getElementById('mnCardTarget')?.classList.add('mn-step-active');
            if (step === 2) document.getElementById('mnCardChannels')?.classList.add('mn-step-active');
            if (step === 3) document.getElementById('mnCardMessage')?.classList.add('mn-step-active');

            const backBtn = document.getElementById('mnWizardBackBtn');
            const nextBtn = document.getElementById('mnWizardNextBtn');
            if (backBtn) backBtn.disabled = step === 1;
            if (nextBtn) nextBtn.textContent = step === 3 ? 'Finish' : 'Next';

            document.getElementById('mnWStep1')?.classList.toggle('is-active', step === 1);
            document.getElementById('mnWStep2')?.classList.toggle('is-active', step === 2);
            document.getElementById('mnWStep3')?.classList.toggle('is-active', step === 3);

            // done states based on current form
            const channels = getSelectedChannels();
            const title = document.getElementById('message_title')?.value?.trim() || '';
            const body = document.getElementById('message_body')?.value?.trim() || '';
            const catId = $('#category_id').val();
            document.getElementById('mnWStep1')?.classList.toggle('is-done', true);
            document.getElementById('mnWStep2')?.classList.toggle('is-done', channels.length > 0);
            document.getElementById('mnWStep3')?.classList.toggle('is-done', !!catId && !!title && !!body);

            if (step === 1) document.getElementById('audienceType')?.focus();
            if (step === 2) document.getElementById('lbl-sms')?.scrollIntoView({behavior:'smooth', block:'center'});
            if (step === 3) document.getElementById('message_title')?.focus();
        }

        function mnWizardNext() {
            if (mnWizardStep === 1) return mnWizardGoTo(2);
            if (mnWizardStep === 2) {
                if (getSelectedChannels().length === 0) {
                    alert('Please select at least one channel.');
                    return;
                }
                return mnWizardGoTo(3);
            }
            // step 3 finish: open preview/confirm modal (non-tech friendly)
            showPreview();
        }

        function mnWizardPrev() {
            if (mnWizardStep > 1) mnWizardGoTo(mnWizardStep - 1);
        }

        function setPreviewMode(mode) {
            previewMode = mode;
            document.querySelectorAll('.mn-preview-mode').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.mode === mode);
            });
            updateLivePreview();
        }

        function toggleAudienceFilters() {
            const type = document.getElementById('audienceType').value;
            document.getElementById('barangayFilter').style.display = type === 'barangay' ? 'block' : 'none';
            document.getElementById('roleFilter').style.display = type === 'role' ? 'block' : 'none';
            document.getElementById('locationFilter').style.display = type === 'location' ? 'block' : 'none';
            
            if (type === 'topic') {
                // Focus user attention on the category dropdown which now serves as the topic filter
                $('#category_id').select2('open');
            }
        }

        function updateSeverityUI(radio) {
            document.querySelectorAll('.severity-radio').forEach(lbl => lbl.classList.remove('selected'));
            radio.parentElement.classList.add('selected');
            mnSyncWeatherSignalFromSeverity();
            updateDispatchCTAState();
        }

        function mnAiAssist() {
            const catId = $('#category_id').val();
            if (!catId) {
                alert('Select an Alert Category first.');
                return;
            }

            const cat = categoriesData.find(c => String(c.id) === String(catId));
            const severity = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();
            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const barangay = document.getElementById('barangay')?.value || '';
            const role = document.getElementById('role')?.value || '';
            const weatherSignal = document.getElementById('mnWeatherSignal')?.value || '';
            const fireLevel = document.getElementById('mnFireLevel')?.value || '';

            const ctx = {
                catName: cat?.name || 'General Alert',
                catDesc: cat?.description || '',
                severity,
                audienceType,
                barangay,
                role,
                weatherSignal,
                fireLevel
            };

            const suggestion = mnGenerateDraft(ctx);
            if (!suggestion) return;

            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');

            const hasExisting = (titleEl?.value || '').trim() || (bodyEl?.value || '').trim();
            if (hasExisting) {
                return mnShowConfirm('Replace your current Title/Message with the suggested draft?', () => {
                    if (titleEl) titleEl.value = suggestion.title;
                    if (bodyEl) bodyEl.value = suggestion.body;
                    if (bodyEl) updateCharCount(bodyEl);
                    updateLivePreview();
                    updateDispatchCTAState();
                });
            }

            if (titleEl) titleEl.value = suggestion.title;
            if (bodyEl) bodyEl.value = suggestion.body;
            if (bodyEl) updateCharCount(bodyEl);
            updateLivePreview();
            updateDispatchCTAState();
        }

        function mnFindCategoryIdByKind(kind) {
            if (!Array.isArray(categoriesData)) return null;
            const match = categoriesData.find(c => mnCategoryKindFromName(c?.name || '') === kind);
            return match?.id ?? null;
        }

        function mnEnsureWizardOpen() {
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            const isOpen = backdrop && getComputedStyle(backdrop).display !== 'none';
            if (!isOpen) openDispatchWizard();
        }

        function mnApplyStarterTemplate(key) {
            mnEnsureWizardOpen();

            if (!Array.isArray(categoriesData) || categoriesData.length === 0) {
                alert('Categories are still loading. Please try again in a moment.');
                return;
            }

            const currentId = $('#category_id').val();
            const currentCat = categoriesData.find(c => String(c.id) === String(currentId));
            const currentKind = mnCategoryKindFromName(currentCat?.name || '');

            const desired = (() => {
                if (key === 'weather_signal') return { kind: 'weather', severity: 'Medium', weatherSignal: '3' };
                if (key === 'fire_level') return { kind: 'fire', severity: 'High', fireLevel: '2' };
                return { kind: null, severity: 'Medium' };
            })();

            let categoryId = currentId || '';
            if (desired.kind) {
                if (currentKind !== desired.kind) {
                    const found = mnFindCategoryIdByKind(desired.kind);
                    if (!found) {
                        alert(`No ${desired.kind} category found. Please add one in Alert Categorization first.`);
                        return;
                    }
                    categoryId = String(found);
                }
            } else {
                if (!categoryId) {
                    // Prefer any non-empty category as a starting point.
                    categoryId = String(categoriesData[0].id);
                }
            }

            // Apply category (Select2-safe).
            $('#category_id').val(categoryId).trigger('change');

            // Apply severity.
            const sevRadio = document.querySelector(`input[name="severity"][value="${desired.severity}"]`);
            if (sevRadio) {
                sevRadio.checked = true;
                updateSeverityUI(sevRadio);
            }

            // Ensure the dynamic level UI is updated for the newly selected category.
            mnUpdateWeatherSignalUI();

            // Apply Weather Signal / Fire Level (mark as user-set so severity changes don't override).
            if (desired.weatherSignal) {
                const sel = document.getElementById('mnWeatherSignal');
                if (sel) {
                    sel.value = String(desired.weatherSignal);
                    sel.dataset.userSet = '1';
                }
            }

            if (desired.fireLevel) {
                const sel = document.getElementById('mnFireLevel');
                if (sel) {
                    sel.value = String(desired.fireLevel);
                    sel.dataset.userSet = '1';
                }
            }

            const cat = categoriesData.find(c => String(c.id) === String(categoryId));
            const severity = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();
            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const barangay = document.getElementById('barangay')?.value || '';
            const role = document.getElementById('role')?.value || '';
            const weatherSignal = document.getElementById('mnWeatherSignal')?.value || '';
            const fireLevel = document.getElementById('mnFireLevel')?.value || '';

            const ctx = {
                catName: cat?.name || 'General Alert',
                catDesc: cat?.description || '',
                severity,
                audienceType,
                barangay,
                role,
                weatherSignal,
                fireLevel
            };

            const suggestion = mnGenerateDraft(ctx);
            if (!suggestion) return;

            const titleEl = document.getElementById('message_title');
            const bodyEl = document.getElementById('message_body');
            const hasExisting = (titleEl?.value || '').trim() || (bodyEl?.value || '').trim();
            if (hasExisting) {
                return mnShowConfirm('Replace your current Title/Message with the selected template?', () => {
                    if (titleEl) titleEl.value = suggestion.title;
                    if (bodyEl) bodyEl.value = suggestion.body;
                    if (bodyEl) updateCharCount(bodyEl);
                    updateLivePreview();
                    updateDispatchCTAState();
                });
            }

            if (titleEl) titleEl.value = suggestion.title;
            if (bodyEl) bodyEl.value = suggestion.body;
            if (bodyEl) updateCharCount(bodyEl);
            updateLivePreview();
            updateDispatchCTAState();
        }

        function mnGenerateDraft(ctx) {
            const name = String(ctx.catName || 'General').trim();
            const n = name.toLowerCase();
            const sev = String(ctx.severity || 'medium').toLowerCase();
            const bullet = '\u2022';

            const severityWord = (s) => {
                if (s === 'low') return 'Advisory';
                if (s === 'medium') return 'Warning';
                if (s === 'high') return 'Urgent Warning';
                if (s === 'critical') return 'Emergency Alert';
                return 'Warning';
            };

            const actionLead = (s) => {
                if (s === 'low') return 'Stay alert and monitor updates.';
                if (s === 'medium') return 'Please take precautions and prepare.';
                if (s === 'high') return 'Take action now and follow safety instructions.';
                if (s === 'critical') return 'ACT NOW for your safety.';
                return 'Please take precautions.';
            };

            const where = (() => {
                if (ctx.audienceType === 'barangay' && ctx.barangay) return `in ${ctx.barangay}, Quezon City`;
                if (ctx.audienceType === 'role' && ctx.role) return `for ${ctx.role} users in Quezon City`;
                return 'in Quezon City';
            })();

            const kind = mnCategoryKindFromName(n);

            const stamp = (() => {
                const d = new Date();
                return d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
            })();

            const titlePrefix = severityWord(sev);

            // Special: Fire alert levels (1–3) mapped from severity
            const fireLevel = (() => {
                if (kind !== 'fire') return null;
                const provided = String(ctx.fireLevel || '').trim();
                if (provided && /^[1-3]$/.test(provided)) return Number(provided);
                return mnDefaultFireLevelFromSeverity(sev);
            })();

            // Special: Weather signal (1–5)
            const weatherSignal = (() => {
                if (kind !== 'weather') return null;
                const provided = String(ctx.weatherSignal || '').trim();
                if (provided && /^[1-5]$/.test(provided)) return Number(provided);
                return mnDefaultWeatherSignalFromSeverity(sev);
            })();

            const title =
                kind === 'fire' ? `Fire Alert Level ${fireLevel}: ${name}` :
                kind === 'weather' ? `Weather Signal ${weatherSignal}: ${name}` :
                `${titlePrefix}: ${name}`;

            const commonTail = [
                'Stay calm and assist children, seniors, and persons with disabilities.',
                'Follow LGU updates and official advisories only.'
            ];

            let bullets = [];
            if (kind === 'earthquake') {
                bullets = [
                    `${actionLead(sev)} Possible aftershocks ${where}.`,
                    'If indoors: DROP, COVER, and HOLD ON.',
                    'If outside: move to an open area away from buildings and wires.',
                    'Check for injuries and hazards (gas leaks, damaged lines).',
                    'Prepare a go-bag and keep phones charged.'
                ];
            } else if (kind === 'weather') {
                bullets = [
                    `${actionLead(sev)} Weather-related risk ${where}. (Signal ${weatherSignal})`,
                    'Secure loose items, check drainage, and keep emergency supplies ready.',
                    "Avoid crossing flooded roads; turn around, don't drown.",
                    'If asked to evacuate: move early to the nearest evacuation site.',
                    'Monitor updates and be ready for sudden changes.'
                ];
            } else if (kind === 'fire') {
                bullets = [
                    `${actionLead(sev)} Fire/smoke hazard reported ${where}. (Alert Level ${fireLevel})`,
                    'Evacuate calmly via the nearest safe exit; do not use elevators.',
                    'If there is smoke: stay low and cover nose/mouth with cloth.',
                    'Do not return until authorities declare the area safe.'
                ];
            } else if (kind === 'landslide') {
                bullets = [
                    `${actionLead(sev)} Landslide risk ${where}.`,
                    'Avoid steep slopes and watch for signs (cracks, falling rocks).',
                    'Move to a safer location if you notice unusual ground movement.',
                    'Prepare for possible evacuation.'
                ];
            } else if (kind === 'tsunami') {
                bullets = [
                    `${actionLead(sev)} Possible tsunami risk ${where}.`,
                    'Move immediately to higher ground away from coasts and rivers.',
                    'Do not return until the all-clear is given.'
                ];
            } else if (kind === 'power') {
                bullets = [
                    `${actionLead(sev)} Power interruption reported ${where}.`,
                    'Keep flashlights ready; avoid open flames indoors.',
                    'Unplug sensitive devices to prevent surge damage.',
                    'Report downed lines; keep a safe distance.'
                ];
            } else {
                bullets = [
                    `${actionLead(sev)} Please be guided ${where}.`,
                    'Follow official instructions and keep emergency contacts reachable.',
                    'If you need help, contact the barangay/LGU hotline.'
                ];
            }

            const headerLine =
                kind === 'fire' ? `FIRE ALERT LEVEL ${fireLevel} ${bullet} ${stamp}` :
                kind === 'weather' ? `WEATHER SIGNAL ${weatherSignal} ${bullet} ${stamp}` :
                `${titlePrefix.toUpperCase()} ${bullet} ${stamp}`;

            const body = [
                headerLine,
                '',
                ...bullets.map(b => `${bullet} ${b}`),
                '',
                ...commonTail.map(t => `${bullet} ${t}`)
            ].join('\n');

            return { title, body };
        }

        function mnCategoryKindFromName(name) {
            const n = String(name || '').toLowerCase();
            if (n.includes('earthquake') || n.includes('aftershock') || n.includes('seismic')) return 'earthquake';
            if (n.includes('flood') || n.includes('typhoon') || n.includes('storm') || n.includes('rain') || n.includes('weather')) return 'weather';
            if (n.includes('fire') || n.includes('smoke') || n.includes('burn')) return 'fire';
            if (n.includes('landslide')) return 'landslide';
            if (n.includes('tsunami')) return 'tsunami';
            if (n.includes('power') || n.includes('outage')) return 'power';
            return 'general';
        }

        function mnDefaultFireLevelFromSeverity(sev) {
            const s = String(sev || '').toLowerCase();
            if (s === 'low') return 1;
            if (s === 'medium') return 2;
            return 3; // high + critical
        }

        function mnDefaultWeatherSignalFromSeverity(sev) {
            const s = String(sev || '').toLowerCase();
            if (s === 'low') return 1;
            if (s === 'medium') return 2;
            if (s === 'high') return 3;
            return 5; // critical
        }

        function mnUpdateWeatherSignalUI() {
            const catId = $('#category_id').val();
            const cat = categoriesData.find(c => c.id == catId);
            const kind = mnCategoryKindFromName(cat?.name || '');

            const weatherWrap = document.getElementById('mnWeatherSignalWrap');
            const weatherSel = document.getElementById('mnWeatherSignal');
            const fireWrap = document.getElementById('mnFireLevelWrap');
            const fireSel = document.getElementById('mnFireLevel');

            const showWeather = kind === 'weather';
            const showFire = kind === 'fire';

            if (weatherWrap) weatherWrap.style.display = showWeather ? 'block' : 'none';
            if (fireWrap) fireWrap.style.display = showFire ? 'block' : 'none';

            const sev = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();

            if (showWeather && weatherSel) {
                if (weatherSel.dataset.userSet !== '1') {
                    const current = String(weatherSel.value || '').trim();
                    if (!/^[1-5]$/.test(current)) {
                        weatherSel.value = String(mnDefaultWeatherSignalFromSeverity(sev));
                    }
                }

                if (!weatherSel.dataset._bound) {
                    weatherSel.addEventListener('change', () => {
                        weatherSel.dataset.userSet = '1';
                        updateLivePreview();
                    });
                    weatherSel.dataset._bound = '1';
                }
            }

            if (showFire && fireSel) {
                if (fireSel.dataset.userSet !== '1') {
                    const current = String(fireSel.value || '').trim();
                    if (!/^[1-3]$/.test(current)) {
                        fireSel.value = String(mnDefaultFireLevelFromSeverity(sev));
                    }
                }

                if (!fireSel.dataset._bound) {
                    fireSel.addEventListener('change', () => {
                        fireSel.dataset.userSet = '1';
                        updateLivePreview();
                    });
                    fireSel.dataset._bound = '1';
                }
            }
        }

        function mnSyncWeatherSignalFromSeverity() {
            const sev = (document.querySelector('input[name="severity"]:checked')?.value || 'Medium').toLowerCase();

            let changed = false;

            const weatherWrap = document.getElementById('mnWeatherSignalWrap');
            const weatherSel = document.getElementById('mnWeatherSignal');
            if (weatherWrap && weatherSel && weatherWrap.style.display !== 'none' && weatherSel.dataset.userSet !== '1') {
                weatherSel.value = String(mnDefaultWeatherSignalFromSeverity(sev));
                changed = true;
            }

            const fireWrap = document.getElementById('mnFireLevelWrap');
            const fireSel = document.getElementById('mnFireLevel');
            if (fireWrap && fireSel && fireWrap.style.display !== 'none' && fireSel.dataset.userSet !== '1') {
                fireSel.value = String(mnDefaultFireLevelFromSeverity(sev));
                changed = true;
            }

            if (changed) updateLivePreview();
        }

        function updateCharCount(textarea) {
            const len = textarea.value.length;
            document.getElementById('charCount').textContent = len;
            document.getElementById('smsParts').textContent = Math.ceil(len / 160);
            
            // Also update preview body text
            const previewBody = document.getElementById('preview-body');
            if (previewBody) {
                previewBody.textContent = textarea.value;
            }
        }

        function mnOpenModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function mnCloseModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');

            // Only unlock body scroll if the wizard isn't open (it uses its own backdrop).
            const wizardOpen = document.getElementById('mnDispatchWizardBackdrop')?.classList?.contains('show');
            if (!wizardOpen) document.body.style.overflow = '';

            if (modalId === 'previewModal') {
                mnPendingDispatchPayload = null;
            }
        }

        function mnPayloadFromPreviewModal() {
            try {
                const channelsText = (document.getElementById('pvChannels')?.textContent || '').trim();
                const channels = channelsText
                    ? channelsText.split(',').map(s => s.trim().toLowerCase()).filter(Boolean)
                    : [];

                const payload = {
                    audience_type: $('#audienceType').val(),
                    barangay: $('#barangay').val(),
                    role: $('#role').val(),
                    category_id: $('#category_id').val(),
                    channels,
                    severity: (document.getElementById('pvSeverity')?.textContent || '').trim() || ($('input[name="severity"]:checked').val() || 'Medium'),
                    title: (document.getElementById('pvTitle')?.textContent || '').trim(),
                    body: (document.getElementById('pvBody')?.textContent || '').trim()
                };
                if (payload.audience_type === 'location') {
                    payload.target_lat = (document.getElementById('mnTargetLat')?.value || '').trim();
                    payload.target_lng = (document.getElementById('mnTargetLng')?.value || '').trim();
                    payload.radius_m = (document.getElementById('mnRadiusM')?.value || '').trim();
                }
                return payload;
            } catch (e) {
                return null;
            }
        }

        function mnBuildDispatchPayload() {
            const data = {
                audience_type: $('#audienceType').val(),
                barangay: $('#barangay').val(),
                role: $('#role').val(),
                category_id: $('#category_id').val(),
                channels: getSelectedChannels(),
                severity: $('input[name="severity"]:checked').val(),
                title: (document.getElementById('message_title')?.value || '').trim(),
                body: (document.getElementById('message_body')?.value || '').trim()
            };

            // Optional QC map target (location mode)
            try {
                const audienceType = data.audience_type;
                const lat = (document.getElementById('mnTargetLat')?.value || '').trim();
                const lng = (document.getElementById('mnTargetLng')?.value || '').trim();
                const radiusM = (document.getElementById('mnRadiusM')?.value || '').trim();
                const addr = (document.getElementById('mnTargetAddress')?.value || '').trim();

                if (audienceType === 'location') {
                    data.target_lat = lat;
                    data.target_lng = lng;
                    data.radius_m = radiusM;
                    if (addr) data.target_address = addr;
                } else if (audienceType === 'barangay') {
                    // Keep coords if admin picked a pin (useful for preview/audit; backend may ignore)
                    if (lat && lng) {
                        data.target_lat = lat;
                        data.target_lng = lng;
                        if (addr) data.target_address = addr;
                    }
                }
            } catch (e) {}

            // Include optional level fields when relevant (backend may ignore if unsupported)
            try {
                const cat = categoriesData.find(c => c.id == data.category_id);
                const kind = mnCategoryKindFromName(cat?.name || '');
                if (kind === 'weather') data.weather_signal = $('#mnWeatherSignal').val();
                if (kind === 'fire') data.fire_level = $('#mnFireLevel').val();
            } catch (e) {}

            return data;
        }

        function loadOptions() {
            fetch('../api/mass-notification.php?action=get_options')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Populate Barangays
                        const bSel = document.getElementById('barangay');
                        bSel.innerHTML = data.barangays.map(b => `<option value="${b}">${b}</option>`).join('');
                        
                        // Populate Categories with metadata
                        if (Array.isArray(data.categories) && data.categories.length > 0) {
                            categoriesData = data.categories;
                            const cSel = document.getElementById('category_id');
                            cSel.innerHTML = '<option value="">-- Select Category --</option>' + 
                                data.categories.map(c => `<option value="${c.id}" data-icon="${c.icon}" data-color="${c.color}" data-description="${(c.description || '').replace(/\"/g,'&quot;')}">${c.name}</option>`).join('');
                            
                            // Initialize Select2 for Categories
                            initCategorySelect();
                        } else {
                            console.warn('Mass Notification: no categories returned from get_options; falling back to alert-categories list.');
                            loadCategoriesFallback();
                        }

                        // Populate Templates
                        const tSel = document.getElementById('template');
                        templatesData = data.templates;
                        tSel.innerHTML = '<option value="">-- Select a Template --</option>' + 
                            data.templates.map(t => `<option value="${t.id}">${t.title} (${t.severity})</option>`).join('');

                        // Re-apply any saved draft now that options are loaded (cookie/localStorage)
                        try { window.DraftPersist?.restoreForm(document.getElementById('dispatchForm')); } catch {}
                    } else {
                        console.error('Mass Notification: get_options failed:', data?.message || data);
                        loadCategoriesFallback();
                    }
                });
        }

        function loadCategoriesFallback() {
            fetch('../api/alert-categories.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success || !Array.isArray(data.categories)) {
                        console.error('Mass Notification: alert-categories list failed:', data?.message || data);
                        const cSel = document.getElementById('category_id');
                        if (cSel) cSel.innerHTML = '<option value="">-- No categories found --</option>';
                        categoriesData = [];
                        return;
                    }

                    // Normalize to the fields expected by preview + AI assist
                    categoriesData = data.categories.map(c => ({
                        id: c.id,
                        name: c.name,
                        icon: c.icon || 'fa-exclamation-triangle',
                        color: c.color || '#4c8a89',
                        description: c.description || ''
                    }));

                    const cSel = document.getElementById('category_id');
                    cSel.innerHTML = '<option value="">-- Select Category --</option>' +
                        categoriesData.map(c => `<option value="${c.id}" data-icon="${c.icon}" data-color="${c.color}" data-description="${(c.description || '').replace(/\"/g,'&quot;')}">${c.name}</option>`).join('');

                    initCategorySelect();
                    try { window.DraftPersist?.restoreForm(document.getElementById('dispatchForm')); } catch {}
                })
                .catch(err => {
                    console.error('Mass Notification: category fallback error:', err);
                });
        }

        function initCategorySelect() {
            try {
                if ($('#category_id').hasClass('select2-hidden-accessible')) {
                    $('#category_id').select2('destroy');
                }
            } catch {}

            function formatCategory(state) {
                if (!state.id) return state.text;
                const icon = $(state.element).data('icon') || 'fa-tag';
                const color = $(state.element).data('color') || '#95a5a6';
                return $(`<span><i class="fas ${icon}" style="color:${color}; width: 20px; text-align: center; margin-right: 8px;"></i>${state.text}</span>`);
            }

            $('#category_id').select2({
                templateResult: formatCategory,
                templateSelection: formatCategory,
                placeholder: "-- Select Category --",
                allowClear: true,
                dropdownParent: $('#mnDispatchWizardBackdrop .mn-modal')
            }).on('change', function () {
                mnUpdateWeatherSignalUI();
                updateLivePreview();
            });

            // Ensure signal UI state matches restored/initial category
            mnUpdateWeatherSignalUI();
        }

        function updateLivePreview() {
            const catId = $('#category_id').val();
            const title = document.getElementById('message_title').value;
            const body = document.getElementById('message_body').value;
            const previewPill = document.getElementById('live-preview-pill');
            const previewName = document.getElementById('preview-name');
            const previewIcon = document.getElementById('preview-icon');
            const previewContent = document.getElementById('preview-content');
            const previewBody = document.getElementById('preview-body');
            const previewFooter = document.getElementById('mnPreviewFooter');

            if (catId) {
                const cat = categoriesData.find(c => c.id == catId);
                if (cat) {
                    previewPill.style.display = 'inline-flex';
                    previewPill.style.background = cat.color;
                    previewIcon.className = `fas ${cat.icon}`;
                    previewName.textContent = cat.name;
                }
            } else {
                previewPill.style.display = 'none';
            }

            previewContent.textContent = title || "Enter a title to see preview...";
            previewContent.style.opacity = title ? "1" : "0.5";
            
            if (previewBody) {
                if (previewMode === 'sms') {
                    previewBody.textContent = body;
                    if (previewFooter) previewFooter.textContent = `SMS preview • ${Math.ceil((title.length + body.length) / 160)} part(s) approx.`;
                } else if (previewMode === 'email') {
                    previewBody.textContent = body ? `Hi,\n\n${body}\n\n- Emergency Communication System` : '';
                    if (previewFooter) previewFooter.textContent = 'Email preview • supports longer instructions.';
                } else if (previewMode === 'push') {
                    previewBody.textContent = body ? body.slice(0, 140) + (body.length > 140 ? '...' : '') : '';
                    if (previewFooter) previewFooter.textContent = 'Push preview • keep it short for lock screens.';
                } else if (previewMode === 'pa') {
                    previewBody.textContent = body ? body.toUpperCase() : '';
                    if (previewFooter) previewFooter.textContent = 'PA preview • uppercase for announcement clarity.';
                } else {
                    previewBody.textContent = body;
                    if (previewFooter) previewFooter.textContent = '';
                }
            }

            // Extra context: show weather signal when applicable
            if (previewFooter) {
                const cat = categoriesData.find(c => c.id == catId);
                const kind = mnCategoryKindFromName(cat?.name || '');
                if (kind === 'weather') {
                    const sig = document.getElementById('mnWeatherSignal')?.value;
                    if (sig) previewFooter.textContent = `${previewFooter.textContent} Signal ${sig}.`;
                } else if (kind === 'fire') {
                    const lvl = document.getElementById('mnFireLevel')?.value;
                    if (lvl) previewFooter.textContent = `${previewFooter.textContent} Level ${lvl}.`;
                }
            }

            updateDispatchCTAState();
        }

        function getSelectedChannels() {
            const channels = [];
            document.querySelectorAll('input[name="channels"]:checked').forEach(el => channels.push(el.value));
            return channels;
        }

        function updateDispatchCTAState() {
            const btn = document.getElementById('mnPreviewDispatchBtn');
            const reason = document.getElementById('mnDispatchReason');
            if (!btn || !reason) return;

            const audienceType = document.getElementById('audienceType')?.value || 'all';
            const title = document.getElementById('message_title').value.trim();
            const body = document.getElementById('message_body').value.trim();
            const catId = $('#category_id').val();
            const channels = getSelectedChannels();
            const lat = (document.getElementById('mnTargetLat')?.value || '').trim();
            const lng = (document.getElementById('mnTargetLng')?.value || '').trim();
            const radiusM = (document.getElementById('mnRadiusM')?.value || '').trim();

            let missing = [];
            if (!catId) missing.push('category');
            if (channels.length === 0) missing.push('channel');
            if (!title) missing.push('title');
            if (!body) missing.push('message');
            if (audienceType === 'location') {
                if (!lat || !lng) missing.push('location');
                const r = parseInt(radiusM || '0', 10);
                if (!Number.isFinite(r) || r <= 0) missing.push('radius');
            }

            const canProceed = missing.length === 0;
            btn.disabled = !canProceed;

            if (!canProceed) {
                reason.classList.add('is-visible');
                const map = {category:'Select a category', channel:'choose at least 1 channel', title:'add a title', message:'write a message', location:'pick a location on the map', radius:'set a valid radius'};
                reason.textContent = 'To continue: ' + missing.map(m => map[m]).join(', ') + '.';
            } else {
                reason.classList.remove('is-visible');
                reason.textContent = '';
            }

            // Stepper state
            const step1 = document.getElementById('mnStep1');
            const step2 = document.getElementById('mnStep2');
            const step3 = document.getElementById('mnStep3');
            if (step1 && step2 && step3) {
                step1.classList.add('is-done');
                step2.classList.toggle('is-done', channels.length > 0);
                step3.classList.toggle('is-done', !!catId && !!title && !!body);

                step1.classList.toggle('is-active', channels.length === 0);
                step2.classList.toggle('is-active', channels.length > 0 && !(catId && title && body));
                step3.classList.toggle('is-active', channels.length > 0 && (catId && title && body));
            }
        }

        // Bind interactions (safe if elements exist)
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('message_title');
            const bodyInput = document.getElementById('message_body');
            if (titleInput) titleInput.addEventListener('input', updateLivePreview);
            if (bodyInput) bodyInput.addEventListener('input', updateLivePreview);

            document.querySelectorAll('input[name="channels"]').forEach(ch => {
                ch.addEventListener('change', updateDispatchCTAState);
            });

            const audienceSel = document.getElementById('audienceType');
            if (audienceSel) audienceSel.addEventListener('change', updateDispatchCTAState);

            const radiusInput = document.getElementById('mnRadiusM');
            if (radiusInput) radiusInput.addEventListener('input', () => {
                updateDispatchCTAState();
                mnUpdateRadiusCircle();
            });

            const targetAddrInput = document.getElementById('mnTargetAddressText');
            if (targetAddrInput) targetAddrInput.addEventListener('input', () => {
                const v = (targetAddrInput.value || '').trim();
                const hidden = document.getElementById('mnTargetAddress');
                if (hidden) hidden.value = v;
                const txt = document.getElementById('mnTargetAddrText');
                if (txt) txt.textContent = v ? `Address: ${v}` : '';
            });

            document.querySelectorAll('.mn-preview-mode').forEach(btn => {
                btn.addEventListener('click', () => setPreviewMode(btn.dataset.mode));
            });

            const step1 = document.getElementById('mnStep1');
            const step2 = document.getElementById('mnStep2');
            const step3 = document.getElementById('mnStep3');
            if (step1) step1.addEventListener('click', () => document.getElementById('audienceType')?.focus());
            if (step2) step2.addEventListener('click', () => document.getElementById('lbl-sms')?.scrollIntoView({behavior:'smooth', block:'center'}));
            if (step3) step3.addEventListener('click', () => document.getElementById('message_title')?.focus());

            updateDispatchCTAState();
        });

        function applyTemplate(id) {
            const t = templatesData.find(tpl => tpl.id == id);
            if (t) {
                document.getElementById('message_title').value = t.title;
                document.getElementById('message_body').value = t.body;
                
                // Update Select2
                $('#category_id').val(t.category_id).trigger('change');

                // Update Severity
                const sevRadio = document.querySelector(`input[name="severity"][value="${t.severity}"]`);
                if (sevRadio) {
                    sevRadio.checked = true;
                    updateSeverityUI(sevRadio);
                }
                updateCharCount(document.getElementById('message_body'));
                updateLivePreview();
            }
        }

        function showPreview() {
            const data = mnBuildDispatchPayload();
            const channels = data.channels || [];

            if (channels.length === 0) {
                alert('Please select at least one channel.');
                return;
            }

            if (!data.category_id) {
                alert('Please select a category.');
                return;
            }

            if (!data.title || !data.body) {
                alert('Please enter a title and message body.');
                return;
            }

            if (data.audience_type === 'location') {
                if (!data.target_lat || !data.target_lng) {
                    alert('Please pick a location on the map first.');
                    return;
                }
                const r = parseInt(data.radius_m || '0', 10);
                if (!Number.isFinite(r) || r <= 0) {
                    alert('Please enter a valid radius in meters.');
                    return;
                }
            }

            document.getElementById('pvAudience').textContent = document.getElementById('audienceType').options[document.getElementById('audienceType').selectedIndex].text;
            document.getElementById('pvChannels').textContent = channels.join(', ').toUpperCase();
            document.getElementById('pvSeverity').textContent = data.severity || 'Medium';
            document.getElementById('pvTitle').textContent = data.title;
            document.getElementById('pvBody').textContent = data.body;

            // Use class-based modal show/hide (compatible with global modal helpers)
            mnOpenModal('previewModal');

            // Cache payload to avoid any DOM timing issues while confirming
            mnPendingDispatchPayload = data;
        }

        function submitDispatch() {
            const btn = document.getElementById('confirmDispatchBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Queuing...';

            let data = mnPendingDispatchPayload || mnBuildDispatchPayload();

            // Guardrails (avoid server-side "required fields missing" errors)
            const missing = [];
            if (!Array.isArray(data.channels) || data.channels.length === 0) missing.push('channels');
            if (!data.title) missing.push('title');
            if (!data.body) missing.push('body');
            if (missing.length > 0) {
                // Fallback: build payload from the already-rendered preview modal
                const fallback = mnPayloadFromPreviewModal();
                if (fallback) data = { ...data, ...fallback };

                const missing2 = [];
                if (!Array.isArray(data.channels) || data.channels.length === 0) missing2.push('channels');
                if (!data.title) missing2.push('title');
                if (!data.body) missing2.push('body');
                if (missing2.length > 0) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Dispatch';
                    alert('Please complete required fields: ' + missing2.join(', '));
                    return;
                }
            }

            // Debug log
            console.log('Dispatching Data:', data);

            // Convert to FormData for backend compatibility if it expects it, 
            // but the prompt says send as JSON or form data.
            // Using jQuery ajax for simple data object transmission as requested.
            $.ajax({
                url: '../api/send-broadcast.php',
                type: 'POST',
                data: data,
                dataType: 'json'
            })
            .done(function(data) {
                // Feature improvement: Close modal immediately before showing alert
                mnCloseModal('previewModal');
                 
                if (data.success) {
                    alert(data.message);
                    document.getElementById('dispatchForm').reset();
                    document.querySelectorAll('.channel-checkbox').forEach(c => c.classList.remove('selected'));
                    $('#category_id').val(null).trigger('change');
                    try { window.DraftPersist?.clearDraft('admin-mn-dispatch'); } catch {}
                    mnPendingDispatchPayload = null;
                    loadNotifications();

                    // Close wizard too to avoid trapping the user under overlays.
                    try { closeDispatchWizard(); } catch (e) {}

                    // Kick the worker once (local/dev friendly) so queued jobs actually send.
                    // Safe to ignore errors; deployments may run the worker via cron.
                    try {
                        fetch('../api/notification-worker.php', { cache: 'no-store' })
                            .then(() => setTimeout(loadNotifications, 800))
                            .catch(() => {});
                    } catch (e) {}
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .fail(function(xhr) {
                console.error('Dispatch Error:', xhr.responseText);
                mnCloseModal('previewModal');
                alert('Connection or Server Error. Please check console.');
            })
            .always(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Dispatch';
            }).finally(() => {
                if (addrText) addrText.classList.remove('is-loading');
                if (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                }
            });
        }

        // Close preview modal on backdrop click + Escape for a smoother UX
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('previewModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.target === modal) mnCloseModal('previewModal');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            const modal = document.getElementById('previewModal');
            if (modal && modal.classList.contains('show')) mnCloseModal('previewModal');
        });

        // --- Map Picker (Quezon City) ---
        function mnOpenMapPicker(mode) {
            mnMapTargetMode = mode === 'barangay' ? 'barangay' : 'location';

            if (!window.L) {
                alert('Map library failed to load. Please check your internet connection, then refresh the page.');
                return;
            }

            mnOpenModal('mnMapModal');
            setTimeout(() => {
                mnInitMapIfNeeded();
                try { mnMap.invalidateSize(); } catch (e) {}
                document.getElementById('mnMapSearch')?.focus();
                if (document.getElementById('mnMapResults') && !document.getElementById('mnMapResults').innerHTML.trim()) {
                    document.getElementById('mnMapResults').innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Search results will appear here.</div>';
                }
                mnUpdateRadiusCircle();
            }, 0);
        }

        function mnCssVar(name, fallback) {
            try {
                const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                return v || fallback;
            } catch (e) {
                return fallback;
            }
        }

        function mnGeoRingContainsPoint(ring, lng, lat) {
            // ring: array of [lng, lat] (GeoJSON order)
            let inside = false;
            for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                const xi = ring[i][0], yi = ring[i][1];
                const xj = ring[j][0], yj = ring[j][1];
                const intersect = ((yi > lat) !== (yj > lat)) && (lng < ((xj - xi) * (lat - yi)) / (yj - yi + 0.0) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        function mnGeoPolygonContainsPoint(coords, lng, lat) {
            if (!Array.isArray(coords) || coords.length === 0) return false;
            const outer = coords[0];
            if (!mnGeoRingContainsPoint(outer, lng, lat)) return false;
            for (let i = 1; i < coords.length; i++) {
                if (mnGeoRingContainsPoint(coords[i], lng, lat)) return false;
            }
            return true;
        }

        function mnGeometryContainsPoint(geometry, lat, lng) {
            if (!geometry) return false;
            if (geometry.type === 'Polygon') {
                return mnGeoPolygonContainsPoint(geometry.coordinates, lng, lat);
            }
            if (geometry.type === 'MultiPolygon') {
                for (const poly of geometry.coordinates || []) {
                    if (mnGeoPolygonContainsPoint(poly, lng, lat)) return true;
                }
                return false;
            }
            return false;
        }

        function mnGeojsonContainsPoint(geojson, lat, lng) {
            try {
                if (!geojson) return false;

                // Supports FeatureCollection, Feature, or bare Geometry
                if (geojson.type === 'FeatureCollection') {
                    const features = geojson.features || [];
                    for (const f of features) {
                        if (mnGeometryContainsPoint(f?.geometry, lat, lng)) return true;
                    }
                    return false;
                }

                if (geojson.type === 'Feature') {
                    return mnGeometryContainsPoint(geojson.geometry, lat, lng);
                }

                if (geojson.type === 'Polygon' || geojson.type === 'MultiPolygon') {
                    return mnGeometryContainsPoint(geojson, lat, lng);
                }
            } catch (e) {}
            return false;
        }

        function mnLoadQcBoundary() {
            if (mnQcGeojson) return Promise.resolve(mnQcGeojson);
            return fetch('../api/quezon-city.geojson', { cache: 'force-cache' })
                .then(r => r.json())
                .then(data => {
                    mnQcGeojson = data;
                    return data;
                })
                .catch(err => {
                    console.error('Mass Notification: failed to load QC GeoJSON boundary:', err);
                    mnQcGeojson = null;
                    return null;
                });
        }

        function mnInitMapIfNeeded() {
            if (mnMap) return;

            const qcCenter = { lat: 14.6760, lng: 121.0437 };
            mnMap = L.map('mnMap', { zoomControl: true }).setView([qcCenter.lat, qcCenter.lng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mnMap);

            // Load and draw QC boundary GeoJSON (same file used by Weather/Earthquake monitoring)
            mnLoadQcBoundary().then((geo) => {
                if (!geo || !mnMap) return;
                if (mnQcLayer) return;

                const brand = mnCssVar('--primary-color-1', '#4c8a89');
                mnQcLayer = L.geoJSON(geo, {
                    style: {
                        color: brand,
                        weight: 3,
                        fillColor: brand,
                        fillOpacity: 0.06,
                        dashArray: '10 6',
                        opacity: 0.95
                    }
                }).addTo(mnMap);

                try {
                    mnQcBounds = mnQcLayer.getBounds();
                    mnMap.setMaxBounds(mnQcBounds.pad(0.05));
                    mnMap.on('drag', function() {
                        try { mnMap.panInsideBounds(mnQcBounds.pad(0.05), { animate: false }); } catch (e) {}
                    });
                } catch (e) {}
            });

            mnMap.on('click', (e) => {
                if (!mnQcGeojson) {
                    alert('Quezon City boundary is still loading. Please try again in a moment.');
                    return;
                }
                if (!mnGeojsonContainsPoint(mnQcGeojson, e.latlng.lat, e.latlng.lng)) {
                    alert('Please pick a location within Quezon City.');
                    return;
                }
                mnSetMapSelection(e.latlng.lat, e.latlng.lng, 'Dropped pin');
            });
        }

        function mnUpdateRadiusCircle() {
            if (!mnMap || !window.L) return;
            if (mnMapTargetMode !== 'location') {
                if (mnMapRadiusCircle) {
                    try { mnMap.removeLayer(mnMapRadiusCircle); } catch (e) {}
                    mnMapRadiusCircle = null;
                }
                return;
            }

            const r = parseInt((document.getElementById('mnRadiusM')?.value || '0'), 10);
            if (!Number.isFinite(r) || r <= 0) return;
            if (mnMapSelected.lat === null || mnMapSelected.lng === null) return;

            const brand = mnCssVar('--primary-color-1', '#4c8a89');
            if (!mnMapRadiusCircle) {
                mnMapRadiusCircle = L.circle([mnMapSelected.lat, mnMapSelected.lng], {
                    radius: r,
                    color: brand,
                    weight: 2,
                    opacity: 0.8,
                    fillColor: brand,
                    fillOpacity: 0.08
                }).addTo(mnMap);
            } else {
                mnMapRadiusCircle.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                mnMapRadiusCircle.setRadius(r);
            }
        }

        function mnReverseGeocode(lat, lng) {
            // Best-effort reverse geocoding (Nominatim)
            // Keep it lightweight and resilient.
            const host = document.getElementById('mnMapSelectedAddress');
            if (host) host.textContent = 'Finding address...';

            const url = `../api/reverse-geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
            return fetch(url, { headers: { 'Accept': 'application/json', 'Accept-Language': 'en' } })
                .then(r => r.json())
                .then(data => {
                    const name = (data && data.success && data.address) ? String(data.address) : '';
                    mnMapSelected.address = name;
                    const text = name ? `Address: ${name}` : 'Address: (not available)';
                    if (host) host.textContent = text;
                    return name;
                })
                .catch(() => {
                    if (host) host.textContent = 'Address: (lookup failed)';
                    mnMapSelected.address = '';
                    return '';
                });
        }

        function mnReverseGeocodeSafe(lat, lng) {
            // A safer reverse-geocoder: timeout + visible loading indicator.
            // Returns a string (may be empty).
            const seq = ++mnReverseGeocodeSeq;

            const mapHost = document.getElementById('mnMapSelectedAddress');
            if (mapHost) {
                mapHost.classList.add('is-loading');
                mapHost.textContent = 'Finding address...';
            }

            const latFixed = Number(lat).toFixed(6);
            const lngFixed = Number(lng).toFixed(6);
            const wLat = (document.getElementById('mnTargetLat')?.value || '').trim();
            const wLng = (document.getElementById('mnTargetLng')?.value || '').trim();
            const wizardMatches = !!wLat && !!wLng && wLat === latFixed && wLng === lngFixed;
            const wizardAddrText = document.getElementById('mnTargetAddrText');
            const wizardBtn = document.getElementById('mnLookupAddressBtn');
            if (wizardMatches && wizardAddrText) {
                wizardAddrText.classList.add('is-loading');
                wizardAddrText.textContent = 'Address: Looking up...';
            }
            if (wizardMatches && wizardBtn) {
                wizardBtn.classList.add('is-loading');
                wizardBtn.disabled = true;
            }

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 6500);
            const url = `../api/reverse-geocode.php?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;

            return fetch(url, { headers: { 'Accept': 'application/json', 'Accept-Language': 'en' }, signal: controller.signal })
                .then((r) => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then((data) => {
                    if (seq !== mnReverseGeocodeSeq) return '';
                    const name = (data && data.success && data.address) ? String(data.address) : '';
                    if (mapHost) mapHost.textContent = name ? `Address: ${name}` : 'Address: (not available)';
                    return name;
                })
                .catch((err) => {
                    if (seq !== mnReverseGeocodeSeq) return '';
                    const timedOut = err && err.name === 'AbortError';
                    if (mapHost) mapHost.textContent = timedOut ? 'Address: (lookup timed out)' : 'Address: (lookup failed)';
                    return '';
                })
                .finally(() => {
                    clearTimeout(timeout);
                    if (seq !== mnReverseGeocodeSeq) return;
                    if (mapHost) mapHost.classList.remove('is-loading');
                    if (wizardMatches && wizardAddrText) wizardAddrText.classList.remove('is-loading');
                    if (wizardMatches && wizardBtn) {
                        wizardBtn.classList.remove('is-loading');
                        wizardBtn.disabled = false;
                    }
                });
        }

        function mnSetMapSelection(lat, lng, label) {
            mnMapSelected.lat = Number(lat);
            mnMapSelected.lng = Number(lng);
            mnMapSelected.label = label || '';

            // Guard: keep selection within QC boundary (GeoJSON)
            if (!mnQcGeojson) {
                alert('Quezon City boundary is still loading. Please try again in a moment.');
                return;
            }
            if (!mnGeojsonContainsPoint(mnQcGeojson, mnMapSelected.lat, mnMapSelected.lng)) {
                alert('Please pick a location within Quezon City.');
                return;
            }

            if (mnMap && window.L) {
                if (!mnMapMarker) {
                    mnMapMarker = L.marker([mnMapSelected.lat, mnMapSelected.lng], { draggable: true }).addTo(mnMap);
                    mnMapMarker.on('dragend', () => {
                        const p = mnMapMarker.getLatLng();
                        // Don’t allow dragging outside QC
                        if (!mnQcGeojson || !mnGeojsonContainsPoint(mnQcGeojson, p.lat, p.lng)) {
                            alert('Pin must stay within Quezon City.');
                            mnMapMarker.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                            return;
                        }
                        mnSetMapSelection(p.lat, p.lng, 'Moved pin');
                    });
                } else {
                    mnMapMarker.setLatLng([mnMapSelected.lat, mnMapSelected.lng]);
                }
                try { mnMap.panTo([mnMapSelected.lat, mnMapSelected.lng]); } catch (e) {}
            }

            document.getElementById('mnMapLat').textContent = mnMapSelected.lat.toFixed(6);
            document.getElementById('mnMapLng').textContent = mnMapSelected.lng.toFixed(6);
            document.getElementById('mnMapSelectedLabel').textContent = mnMapSelected.label ? `Label: ${mnMapSelected.label}` : '';

            // Update radius preview + reverse geocode (debounced)
            mnUpdateRadiusCircle();
            if (mnReverseGeocodeTimer) clearTimeout(mnReverseGeocodeTimer);
            mnReverseGeocodeTimer = setTimeout(() => {
                mnReverseGeocodeSafe(mnMapSelected.lat, mnMapSelected.lng);
            }, 350);
        }

        async function mnMapDoSearch() {
            const q = (document.getElementById('mnMapSearch')?.value || '').trim();
            const resultsHost = document.getElementById('mnMapResults');
            if (!resultsHost) return;
            resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Searching...</div>';

            if (!q) {
                resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Type a search query above.</div>';
                return;
            }

            // Nominatim search limited to PH + Quezon City viewbox
            // viewbox = west, north, east, south
            let viewbox = '120.95,14.78,121.15,14.57';
            try {
                if (mnQcBounds) {
                    const sw = mnQcBounds.getSouthWest();
                    const ne = mnQcBounds.getNorthEast();
                    viewbox = `${sw.lng},${ne.lat},${ne.lng},${sw.lat}`;
                }
            } catch (e) {}
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=8&countrycodes=ph&bounded=1&viewbox=${encodeURIComponent(viewbox)}&q=${encodeURIComponent(q + ' Quezon City')}`;

            try {
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();

                if (!Array.isArray(data) || data.length === 0) {
                    resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">No results found in Quezon City.</div>';
                    return;
                }

                resultsHost.innerHTML = data.map(item => {
                    const name = (item.display_name || '').split(',').slice(0, 3).join(', ');
                    const lat = Number(item.lat);
                    const lon = Number(item.lon);
                    const safeName = String(name).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return `<div class="mn-map-result" role="button" tabindex="0" onclick="mnSetMapSelection(${lat}, ${lon}, ${JSON.stringify(name)})">${safeName}</div>`;
                }).join('');
            } catch (e) {
                resultsHost.innerHTML = '<div class="mn-map-result" style="opacity:.7; cursor:default;">Search failed. Please try again.</div>';
            }
        }

        function mnMapApplySelection() {
            if (mnMapSelected.lat === null || mnMapSelected.lng === null) {
                alert('Please click on the map or choose a search result first.');
                return;
            }

            const latStr = mnMapSelected.lat.toFixed(6);
            const lngStr = mnMapSelected.lng.toFixed(6);

            document.getElementById('mnTargetLat').value = latStr;
            document.getElementById('mnTargetLng').value = lngStr;

            const prettyAddr = (mnMapSelected.address || '').trim();
            const label = prettyAddr || (mnMapSelected.label ? mnMapSelected.label : `${latStr}, ${lngStr}`);
            const labelHost = document.getElementById('mnTargetLabel');
            if (labelHost) labelHost.textContent = label;

            const coordsHost = document.getElementById('mnTargetCoords');
            const radiusM = parseInt((document.getElementById('mnRadiusM')?.value || '0'), 10);
            const radiusLabel = Number.isFinite(radiusM) && radiusM > 0 ? ` \u2022 Radius: ${radiusM} m` : '';
            if (coordsHost) coordsHost.textContent = `Coordinates: ${latStr}, ${lngStr}${radiusLabel}`;

            const addrHidden = document.getElementById('mnTargetAddress');
            if (addrHidden) addrHidden.value = prettyAddr;

            const addrText = document.getElementById('mnTargetAddrText');
            const btn = document.getElementById('mnLookupAddressBtn');
            if (addrText) addrText.textContent = prettyAddr ? `Address: ${prettyAddr}` : 'Address: Looking up...';

            // Ensure "Looking up" never gets stuck visually (spinner + clear fallback)
            if (addrText) {
                addrText.classList.toggle('is-loading', !prettyAddr);
                if (!prettyAddr) addrText.textContent = 'Address: Looking up...';
            }

            const addrInput = document.getElementById('mnTargetAddressText');
            if (addrInput) addrInput.value = prettyAddr;

            if (mnMapTargetMode === 'barangay') {
                const hint = document.getElementById('mnBarangayCoordsHint');
                if (hint) hint.textContent = `Pin saved: ${latStr}, ${lngStr}`;
            }

            mnCloseModal('mnMapModal');
            updateDispatchCTAState();

            // If address hasn't resolved yet, look it up after closing the map modal
            if (!prettyAddr) {
                mnReverseGeocodeSafe(mnMapSelected.lat, mnMapSelected.lng).then((addr) => {
                    const resolved = (addr || '').trim();
                    if (!resolved) {
                        const aText = document.getElementById('mnTargetAddrText');
                        if (aText) aText.classList.remove('is-loading');
                        if (aText) aText.textContent = 'Address: (not available) - type it in the field above.';
                        return;
                    }
                    const aHidden = document.getElementById('mnTargetAddress');
                    if (aHidden) aHidden.value = resolved;
                    const aText = document.getElementById('mnTargetAddrText');
                    if (aText) aText.classList.remove('is-loading');
                    if (aText) aText.textContent = `Address: ${resolved}`;
                    const aInput = document.getElementById('mnTargetAddressText');
                    if (aInput && !aInput.value.trim()) aInput.value = resolved;
                    // Keep label friendly (do not overwrite a user-entered label)
                    const lHost = document.getElementById('mnTargetLabel');
                    if (lHost && lHost.textContent === 'Dropped pin') lHost.textContent = resolved;
                }).catch(() => {
                    const aText = document.getElementById('mnTargetAddrText');
                    if (aText) {
                        aText.classList.remove('is-loading');
                        aText.textContent = 'Address: (lookup failed) - type it in the field above.';
                    }
                });
            }
        }

                function mnLookupAddressFromWizard() {
            const lat = Number((document.getElementById('mnTargetLat')?.value || '').trim());
            const lng = Number((document.getElementById('mnTargetLng')?.value || '').trim());
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                alert('Pick a location on the map first.');
                return;
            }
            const addrText = document.getElementById('mnTargetAddrText');
            const btn = document.getElementById('mnLookupAddressBtn');
            if (addrText) {
                addrText.classList.add('is-loading');
                addrText.textContent = 'Address: Looking up...';
            }
            if (btn) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
            mnReverseGeocodeSafe(lat, lng).then((addr) => {
                const resolved = (addr || '').trim();
                if (!resolved) {
                    if (addrText) addrText.textContent = 'Address: (not available) - you can type it in the field above.';
                    return;
                }
                const aHidden = document.getElementById('mnTargetAddress');
                if (aHidden) aHidden.value = resolved;
                if (addrText) addrText.textContent = `Address: ${resolved}`;
                const aInput = document.getElementById('mnTargetAddressText');
                if (aInput) aInput.value = resolved;
            }).catch(() => {
                if (addrText) addrText.textContent = 'Address: (lookup failed) - you can type it in the field above.';
            }).finally(() => {
                if (addrText) addrText.classList.remove('is-loading');
                if (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                }
            });
        }

        // Close map modal on backdrop click + Escape
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('mnMapModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.target === modal) mnCloseModal('mnMapModal');
        });

        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('mnMapModal');
            if (!modal || !modal.classList.contains('show')) return;
            if (e.key === 'Escape') {
                mnCloseModal('mnMapModal');
                return;
            }
            if (e.key === 'Enter' && document.activeElement === document.getElementById('mnMapSearch')) {
                e.preventDefault();
                mnMapDoSearch();
            }
        });

        function loadNotifications() {
            fetch('../api/mass-notification.php?action=list')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const tbody = document.querySelector('#notificationsTable tbody');
                    tbody.innerHTML = data.notifications.map(n => {
                        const progress = n.progress || 0;
                        const stats = n.stats || {sent: 0, failed: 0, total: 0};
                        return `
                            <tr>
                                <td>#${n.id}</td>
                                <td><small style="color: var(--text-secondary-1); font-weight: 500;">${n.recipients}</small></td>
                                <td>${n.channel.split(',').map(c => `<i class="fas fa-${getIcon(c)}" title="${c}" style="color: var(--text-secondary-1); margin-right: 4px;"></i>`).join(' ')}</td>
                                <td><div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size: 0.9rem;">${n.message}</div></td>
                                <td>
                                    <span class="badge ${n.status}">${n.status.toUpperCase()}</span>
                                    <div class="progress-container" title="${progress}% sent"><div class="progress-bar" style="width: ${progress}%"></div></div>
                                </td>
                                <td><small style="color: var(--text-secondary-1);">${n.sent_at}</small></td>
                                <td>
                                    ${n.status === 'completed' ? `<strong>${Math.round((stats.sent/stats.total)*100)}%</strong> <br><small style="color: var(--text-secondary-1);">${stats.sent}/${stats.total}</small>` : '--'}
                                </td>
                            </tr>
                        `;
                    }).join('');
                    updateMnAnalytics(data.notifications);
                });
        }

        function updateMnAnalytics(notifications) {
            const total = notifications.length;
            const completed = notifications.filter(n => n.status === 'completed').length;
            const inProgress = notifications.filter(n => n.status === 'sending' || n.status === 'queued').length;

            let successSent = 0;
            let successTotal = 0;
            notifications.forEach(n => {
                if (n.status === 'completed' && n.stats && n.stats.total) {
                    successSent += Number(n.stats.sent || 0);
                    successTotal += Number(n.stats.total || 0);
                }
            });
            const rate = successTotal > 0 ? Math.round((successSent / successTotal) * 100) : 0;

            document.getElementById('mnTotalDispatches').textContent = total;
            document.getElementById('mnCompletedDispatches').textContent = completed;
            document.getElementById('mnInProgressDispatches').textContent = inProgress;
            document.getElementById('mnSuccessRate').textContent = rate;
            const sub = document.getElementById('mnSuccessRateSub');
            if (sub) sub.textContent = successTotal > 0 ? `${successSent}/${successTotal} delivered` : 'Based on completed';
        }

        function getIcon(channel) {
            switch(channel) {
                case 'sms': return 'sms';
                case 'email': return 'envelope';
                case 'push': return 'mobile-alt';
                case 'pa': return 'bullhorn';
                default: return 'broadcast-tower';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadOptions();
            loadNotifications();
            // Poll for updates every 10 seconds
            setInterval(loadNotifications, 10000);

            // Close wizard on backdrop click / escape
            const backdrop = document.getElementById('mnDispatchWizardBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) closeDispatchWizard();
                });
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const bd = document.getElementById('mnDispatchWizardBackdrop');
                    if (bd && bd.classList.contains('show')) closeDispatchWizard();
                }
            });
        });
    
