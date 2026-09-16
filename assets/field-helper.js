// Ensure window.SHAREDFUNCTIONS exists (create if needed, but don't overwrite if it already exists)
if (typeof window.SHAREDFUNCTIONS === 'undefined') {
    window.SHAREDFUNCTIONS = {};
}

// Always add/update these methods to the existing SHAREDFUNCTIONS object
// This ensures they're available even if shared-functions.js overwrites the object later
window.SHAREDFUNCTIONS.formatDate = window.SHAREDFUNCTIONS.formatDate || function(timestamp) {
    return new Date(timestamp * 1000).toLocaleDateString();
};

window.SHAREDFUNCTIONS.formatComment = window.SHAREDFUNCTIONS.formatComment || function(comment) {
    return comment; // Simple implementation
};

window.SHAREDFUNCTIONS.addLink = window.SHAREDFUNCTIONS.addLink || function(event) {
    // Handle add link functionality for link fields
    const linkType = jQuery(event.target).data('link-type');
    const fieldKey = jQuery(event.target).data('field-key');
    if (!linkType || !fieldKey) {
        return;
    }

    // Find the template for this link type
    const template = jQuery(`#link-template-${fieldKey}-${linkType}`);
    if (template.length === 0) {
        return;
    }

    // Clone the template content
    const newLinkInput = template.html();

    // Find the target section for this link type
    const targetSection = jQuery(`.link-section--${linkType}`);
    if (targetSection.length === 0) {
        return;
    }

    // Append the new input to the target section
    targetSection.append(newLinkInput);

    // Focus the new input
    targetSection.find('input').last().focus();
};

/**
 * Link field event handlers
 */
// Handle add link option clicks
jQuery(document).on('click', '.add-link__option', function(event) {
    event.stopPropagation(); // Prevent event from bubbling to avoid triggering modal close handlers
    event.preventDefault(); // Prevent default behavior
    window.SHAREDFUNCTIONS.addLink(event);
    jQuery(event.target).parent().hide();
    setTimeout(() => {
        event.target.parentElement.removeAttribute('style');
    }, 100);
});

// Handle link delete button clicks
jQuery(document).on('click', '.link-delete-button', function() {
    jQuery(this).closest('.input-group').remove();
});

// Handle add button clicks for link fields
jQuery(document).on('click', 'button.add-button', function(e) {
    const field = jQuery(e.currentTarget).data('list-class');
    const fieldType = jQuery(e.currentTarget).data('field-type');

    if (fieldType === 'link') {
        const addLinkForm = jQuery(`.add-link-${field}`);
        addLinkForm.show();

        jQuery(`#cancel-link-button-${field}`).on('click', () => addLinkForm.hide());
    }
});

// Clear dt-date component similar to create-record (updateTimestamp(''))
jQuery(document).on('click', '.clear-date-button', function (evt) {
    const tr = jQuery(this).closest('tr');
    const inputId = jQuery(this).data('inputid');
    if (!inputId) return;
    const comp = tr.find('#' + inputId);
    if (comp.length) {
        const el = comp.get(0);
        if (el && typeof el.updateTimestamp === 'function') {
            try { el.updateTimestamp(''); } catch (e) {}
        }
        comp.attr('value', '');
    } else {
        tr.find('#' + inputId).val('');
    }
    tr.find('#form_content_table_field_meta').val('');
});

// Function to ensure our custom methods are always available on SHAREDFUNCTIONS
// This function can be called multiple times to re-add methods if SHAREDFUNCTIONS gets overwritten
function ensureSharedFunctionsMethods() {
    // Ensure SHAREDFUNCTIONS exists
    if (typeof window.SHAREDFUNCTIONS === 'undefined') {
        window.SHAREDFUNCTIONS = {};
    }

    // Collect DT and custom fields from the Single Record UI
    // Always add/update collectFields to ensure it's available even if SHAREDFUNCTIONS gets overwritten
    window.SHAREDFUNCTIONS.collectFields = function (options) {
        const template = (options && options.template) ? options.template : { fields: [] };

        const payloadFields = { dt: [], custom: [] };

        // Iterate over table rows (DT and custom inside the table for single-record)
        jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {
            const field_id = jQuery(tr).find('#form_content_table_field_id').val();
            const field_type = jQuery(tr).find('#form_content_table_field_type').val();
            const field_template_type = jQuery(tr).find('#form_content_table_field_template_type').val();
            const field_meta = jQuery(tr).find('#form_content_table_field_meta');

            const template_fields = (template.fields || []).filter(function (f) { return f.id === field_id; });
            if (template_fields && template_fields.length && template_fields[0].readonly) {
                return;
            }

            const selector = '#' + field_id;
            if (field_template_type === 'dt') {
                switch (field_type) {
                    case 'text':
                    case 'textarea': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value') || jQuery(tr).find(selector).val();
                        if (rawValue && String(rawValue).trim() !== '') {
                            payloadFields.dt.push({
                                id: field_id,
                                dt_type: field_type,
                                template_type: field_template_type,
                                value: String(rawValue).trim()
                            });
                        }
                        break;
                    }
                    case 'key_select': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value') || jQuery(tr).find(selector).val();
                        if (rawValue && String(rawValue).trim() !== '') {
                            payloadFields.dt.push({
                                id: field_id,
                                dt_type: field_type,
                                template_type: field_template_type,
                                value: String(rawValue).trim()
                            });
                        }
                        break;
                    }
                    case 'number': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value') || jQuery(tr).find(selector).val();
                        if (rawValue !== undefined && rawValue !== null && String(rawValue).trim() !== '') {
                            const numericValue = parseFloat(String(rawValue).trim());
                            if (!isNaN(numericValue)) {
                                payloadFields.dt.push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: numericValue
                                });
                            }
                        }
                        break;
                    }
                    case 'boolean': {
                        const dtComponent = document.getElementById(field_id);
                        let rawValue = dtComponent.value;
                        let boolValue;
                        if (rawValue !== undefined) {
                            boolValue = (rawValue === true || rawValue === 'true' || rawValue === '1' || rawValue === 1 || rawValue === 'on');
                        } else {
                            boolValue = jQuery(tr).find(selector).is(':checked');
                        }
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: !!boolValue
                        });
                        break;
                    }
                    case 'communication_channel': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value');
                        let values = [];
                        if (rawValue) {
                            try {
                                const parsed = JSON.parse(rawValue);
                                if (Array.isArray(parsed)) {
                                    parsed.forEach(function (item) {
                                        if (item && item.value && String(item.value).trim() !== '') {
                                            values.push({ value: String(item.value).trim(), delete: item.delete, key: item.key });
                                        }
                                    });
                                }
                            } catch (e) {}
                        }
                        if (values.length === 0) {
                            jQuery(tr).find('.input-group').each(function () {
                                values.push({
                                    'key': jQuery(this).find('button').data('key'),
                                    'value': jQuery(this).find('input').val()
                                });
                            });
                        }
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: values,
                        });
                        break;
                    }
                    case 'multi_select': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value');
                        let options = [];
                        if (rawValue) {
                            try {
                                const parsed = JSON.parse(rawValue);
                                if (Array.isArray(parsed) && parsed.length > 0) {
                                    parsed.forEach(function (selectedKey) {
                                        const to_delete = selectedKey.includes('-')
                                        if (selectedKey && String(selectedKey).trim() !== '') {
                                            options.push({ value: String(selectedKey).trim().replace('-', ''), delete: to_delete });
                                        }
                                    });
                                }
                            } catch (e) {}
                        }
                        if (options.length === 0) {
                            jQuery(tr).find('button').each(function () {
                                options.push({
                                    'value': jQuery(this).attr('id'),
                                    'delete': jQuery(this).hasClass('empty-select-button')
                                });
                            });
                        }
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: options
                        });
                        break;
                    }
                    case 'date': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value') || '';
                        let dateValue = String(rawValue).trim() || field_meta.val();
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: dateValue
                        });
                        break;
                    }
                    case 'tags': {
                        const dtComponent = document.getElementById(field_id);
                        if (dtComponent) {
                            let tags = dtComponent.value || [];
                            if (!Array.isArray(tags)) tags = [tags];

                            let activeTags = [];
                            let deletedTags = [];

                            tags.forEach(tagName => {
                                if (typeof tagName === 'string' && tagName.startsWith('-')) {
                                    const cleanName = tagName.substring(1);
                                    if (!deletedTags.some(tag => tag.name === cleanName)) {
                                        deletedTags.push({ name: cleanName });
                                    }
                                } else {
                                    if (!activeTags.some(tag => tag.name === tagName)) {
                                        activeTags.push({ name: tagName });
                                    }
                                }
                            });

                            activeTags = activeTags.filter((v, i, a) => a.findIndex(t => (t.name === v.name)) === i);
                            deletedTags = deletedTags.filter((v, i, a) => a.findIndex(t => (t.name === v.name)) === i);

                            deletedTags = deletedTags.filter(d => !activeTags.some(a => a.name === d.name));

                            payloadFields.dt.push({
                                id: field_id, 
                                dt_type: field_type, 
                                template_type: field_template_type, 
                                value: activeTags,
                                deletions: deletedTags
                            });
                        }
                        break;
                    }
                    case 'location': {
                        const dtLocationComponent = document.getElementById(field_id);
                        if (dtLocationComponent) {
                            let locationValue = dtLocationComponent.value;

                            if (locationValue) {
                                let locationData = [];
                                let deletions = [];

                                try {
                                    // Parse JSON string to object/array
                                    const parsed = typeof locationValue === 'string' ? JSON.parse(locationValue) : locationValue;

                                    if (Array.isArray(parsed)) {
                                        parsed.forEach(function(location) {
                                            if (location && location.id) {
                                                if (location.delete) {
                                                    // Add to deletions array
                                                    deletions.push({ ID: location.id });
                                                } else {
                                                    // Add to values array (transform id to ID for endpoint)
                                                    locationData.push({ ID: location.id });
                                                }
                                            }
                                        });
                                    }
                                } catch (e) {
                                    console.error('Error parsing location data for field ' + field_id + ':', e);
                                }

                                // Only add if we have data
                                if (locationData.length > 0 || deletions.length > 0) {
                                    payloadFields.dt.push({
                                        id: field_id,
                                        dt_type: field_type,
                                        template_type: field_template_type,
                                        value: locationData,
                                        deletions: deletions
                                    });
                                }
                            }
                        }
                        break;
                    }
                    case 'location_meta': {
                        const dtLocationMetaComponent = document.getElementById(field_id);
                        if (dtLocationMetaComponent) {
                            const rawValues = dtLocationMetaComponent.value || [];
                            let formattedValues = {};
                            formattedValues[field_id] = {
                                values: rawValues
                            };

                            let previousValues = (options.post && options.post[field_id]) ? options.post[field_id] : [];
                            let deletions = previousValues.filter(prev => {
                                return !rawValues.some(curr => curr.grid_meta_id === prev.grid_meta_id);
                            }).map(prev => prev.grid_meta_id);

                            payloadFields.dt.push({
                                id: field_id,
                                dt_type: field_type,
                                template_type: field_template_type,
                                value: formattedValues,
                                deletions: deletions,
                            });
                        }
                        break;
                    }
                    case 'link': {
                        const dtComponent = document.getElementById(field_id);
                        let rawValue = dtComponent.getAttribute('value');
                        let values = [];
                        if (rawValue) {
                            try {
                                const parsed = JSON.parse(rawValue);
                                if (Array.isArray(parsed)) {
                                    parsed.forEach(function (item) {
                                        if (item && item.value && String(item.value).trim() !== '') {
                                            values.push({ value: String(item.value).trim(), delete: item.delete, key: item.meta_id ? item.meta_id : item.tempKey, type: item.type });
                                        }
                                    });
                                }
                            } catch (e) {}
                        }
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: values,
                        });
                        break;
                    }
                    default:
                        break;
                }
            }
        });

        // Handle dynamically added link inputs (create-record pattern)
        jQuery('.link-input').each(function (index, entry) {
            let fieldKey = jQuery(entry).data('field-key');
            const existing_link_values = options.post[fieldKey] || [];
            const meta_id = parseInt(jQuery(entry).data('meta-id'));
            let type = jQuery(entry).data('type');
            let existingField = payloadFields.dt.find(function (f) { return f.id === fieldKey; });
            if (jQuery(entry).val()) {
                if (!existingField) {
                    existingField = { id: fieldKey, dt_type: 'link', template_type: 'dt', value: { values: [] } };
                    payloadFields.dt.push(existingField);
                }
                if (!existingField.value.values) {
                    existingField.value = { values: [] };
                }
                existingField.value.values.push({ value: jQuery(entry).val(), type: type, meta_id: meta_id });
            }
            //set delete:true to existing values that are not in payloadFields
            existing_link_values.forEach(function(value) {
                if (!payloadFields.dt.find(f => f.id === fieldKey && f.value.values.find(v => v.meta_id === parseInt(value.meta_id)))) {
                    existingField.value.values.push({ meta_id: parseInt(value.meta_id), delete: true });
                }
            });
        });

        // Handle custom fields
        jQuery('.form-field[data-template-type="custom"]').each(function (idx, fieldDiv) {
            let field_id = jQuery(fieldDiv).data('field-id');
            let fieldInput = jQuery(fieldDiv).find('input, textarea');
            if (fieldInput.length > 0) {
                let value = fieldInput.val();
                if (value && value.trim() !== '') {
                    payloadFields.custom.push({
                        id: field_id,
                        template_type: 'custom',
                        value: value,
                        field_type: fieldInput.is('textarea') ? 'textarea' : 'text'
                    });
                }
            }
        });

        return payloadFields;
    };

    // Set DT and custom field values in the Single Record UI from a post object
    // Always add/update setFieldsFromPost to ensure it's available even if SHAREDFUNCTIONS gets overwritten
    window.SHAREDFUNCTIONS.setFieldsFromPost = function (post) {
        if (!post) return;

        jQuery('.form-content-table > tbody > tr').each(function (idx, tr) {
            const field_id = jQuery(tr).find('#form_content_table_field_id').val();
            const field_type = jQuery(tr).find('#form_content_table_field_type').val();
            const field_template_type = jQuery(tr).find('#form_content_table_field_template_type').val();
            const field_meta = jQuery(tr).find('#form_content_table_field_meta');
            const selector = '#' + field_id;

            if (field_template_type !== 'dt') {
                jQuery(tr).find(selector).val('');
                return;
            }

            // If a DT web component is present, reset it before populating
            const existingComponent = jQuery(tr).find('[id="' + field_id + '"]');
            if (existingComponent.length && field_id !== 'name') {
                const el = existingComponent.get(0);
                if (el && typeof el.reset === 'function') {
                    try { el.reset(); } catch (e) {}
                }
            }

            switch (field_type) {
                case 'number':
                case 'textarea':
                case 'text': {
                    const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                    if (dtComponent.length) {
                        dtComponent.attr('value', post[field_id] ? post[field_id] : '');
                    } else {
                        jQuery(tr).find(selector).val(post[field_id] ? post[field_id] : '');
                    }
                    break;
                }
                case 'link': {
                    const dtComponent = document.getElementById(field_id);
                    const data = post[field_id];
                    
                    if (dtComponent) {
                        dtComponent.setAttribute('value', JSON.stringify(data));
                    }

                    // Ensure meta helper is cleared
                    field_meta.val('');
                    break;
                }
                case 'key_select': {
                    const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                    const keyValue = (post[field_id] && post[field_id]['key']) ? post[field_id]['key'] : '';
                    if (dtComponent.length) {
                        dtComponent.attr('value', keyValue);
                    } else {
                        jQuery(tr).find(selector).val(keyValue);
                    }
                    break;
                }
                case 'communication_channel': {
                    const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                    if (dtComponent.length) {
                        const values = Array.isArray(post[field_id]) ? post[field_id].map(function (v) { return { value: v.value || '', key: v.key || '' }; }) : [];
                        dtComponent.attr('value', JSON.stringify(values));
                        field_meta.val('');
                    } else {
                        jQuery(tr).find('.channel-delete-button[data-field="' + field_id + '"]').each(function (idx, del_button) {
                            jQuery(del_button).parent().parent().remove();
                        });
                        if (post[field_id]) {
                            post[field_id].forEach(function (option) {
                                jQuery(tr).find('button.add-button').trigger('click');
                                jQuery(tr).find('.input-group').last().find('input').val(option['value']);
                                if (option['key']) {
                                    jQuery(tr).find('.input-group').last().find('button').attr('data-key', window.lodash.escape(option['key']));
                                }
                            });
                        }
                        field_meta.val('');
                    }
                    break;
                }
                case 'multi_select': {
                    const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                    if (dtComponent.length) {
                        const selected = Array.isArray(post[field_id]) ? post[field_id] : [];
                        dtComponent.attr('value', JSON.stringify(selected));
                    } else {
                        jQuery(tr).find('button').each(function () {
                            jQuery(this).addClass('empty-select-button');
                            jQuery(this).removeClass('selected-select-button');
                            if (post[field_id] && (jQuery(this).data('field-key') === field_id) && post[field_id].includes(jQuery(this).attr('id'))) {
                                jQuery(this).removeClass('empty-select-button');
                                jQuery(this).addClass('selected-select-button');
                            }
                        });
                    }
                    break;
                }
                case 'boolean': {
                    const dtComponent = document.getElementById(field_id);
                    if (dtComponent) {
                        dtComponent.setAttribute('value', !!post[field_id]);
                    } else {
                        jQuery(tr).find(selector).prop('checked', post[field_id]);
                    }
                    break;
                }
                case 'date': {
                    const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                    if (dtComponent.length) {
                        const formatted = (post[field_id] && post[field_id]['formatted']) ? post[field_id]['formatted'] : '';
                        dtComponent.attr('value', formatted);
                        field_meta.val(formatted);
                    } else {
                        if (post[field_id] && post[field_id]['timestamp']) {
                            let timestamp = post[field_id]['timestamp'];
                            const dateObj = new Date(timestamp * 1000);
                            jQuery(tr).find(selector).data('daterangepicker').setStartDate(dateObj);
                            const display = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                            jQuery(tr).find(selector).val(display);
                            const yyyy = dateObj.getFullYear();
                            const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const dd = String(dateObj.getDate()).padStart(2, '0');
                            field_meta.val(`${yyyy}-${mm}-${dd}`);
                        } else {
                            jQuery(tr).find(selector).val('');
                            field_meta.val('');
                        }
                    }
                    break;
                }
                case 'tags': {
                    const dtComponent = document.getElementById(field_id);
                    if (dtComponent) {
                        const tags = Array.isArray(post[field_id]) ? post[field_id] : [];
                        dtComponent.setAttribute('value', JSON.stringify(tags));
                        field_meta.val('');
                    } else {
                        jQuery(tr).find('span.typeahead__cancel-button').trigger('click');
                        let typeahead_tags_field_input = '.js-typeahead-' + field_id;
                        let typeahead_tags = window.Typeahead[typeahead_tags_field_input];
                        typeahead_tags.items = [];
                        typeahead_tags.comparedItems = [];
                        typeahead_tags.label.container.empty();
                        if (post[field_id] && typeahead_tags) {
                            post[field_id].forEach(function (tag) {
                                typeahead_tags.addMultiselectItemLayout({ name: window.lodash.escape(tag) });
                                typeahead_tags.adjustInputSize();
                            });
                        }
                        field_meta.val('');
                    }
                    break;
                }
                case 'location':
                case 'location_meta': {
                    const dtComponent = document.getElementById(field_id);                    
                    if (dtComponent) {
                        const locations = Array.isArray(post[field_id]) ? post[field_id] : [];
                        dtComponent.setAttribute('value', JSON.stringify(locations));
                        field_meta.val('');
                    } else if (field_type === 'location_meta') {
                        jQuery(tr).find('#mapbox-search').val('');
                        const deleteButtons = jQuery(tr).find('#location-grid-meta-results .mapbox-delete-button');
                        if (deleteButtons.length) {
                            deleteButtons.each(function(idx, button) {
                                jQuery(button).parent().parent().remove();
                            });
                        }
                        let lgm_results = jQuery(tr).find('#location-grid-meta-results');
                        if (post[field_id] !== undefined && post[field_id].length !== 0) {
                            jQuery.each(post[field_id], function (i, v) {
                                if (v.grid_meta_id) {
                                    lgm_results.append('<div class="input-group">\
                                        <input type="text" class="active-location input-group-field" id="location-' + window.lodash.escape(v.grid_meta_id) + '" dir="auto" value="' + window.lodash.escape(v.label) + '" readonly />\
                                        <div class="input-group-button">\
                                          <button type="button" class="button success delete-button-style open-mapping-grid-modal" title="' + window.lodash.escape(jsObject["mapbox"]["translations"]["open_modal"]) + '" data-id="' + window.lodash.escape(v.grid_meta_id) + '"><i class="fi-map"></i></button>\
                                          <button type="button" class="button alert delete-button-style delete-button mapbox-delete-button" title="' + window.lodash.escape(jsObject["mapbox"]["translations"]["delete_location"]) + '" data-id="' + window.lodash.escape(v.grid_meta_id) + '">&times;</button>\
                                        </div>\
                                      </div>');
                                } else {
                                    lgm_results.append('<div class="input-group">\
                                        <input type="text" class="dt-communication-channel input-group-field" id="' + window.lodash.escape(v.key) + '" value="' + window.lodash.escape(v.label) + '" dir="auto" data-field="contact_address" />\
                                        <div class="input-group-button">\
                                          <button type="button" class="button success delete-button-style open-mapping-address-modal"\
                                              title="' + window.lodash.escape(jsObject["mapbox"]["translations"]["open_modal"]) + '"\
                                              data-id="' + window.lodash.escape(v.key) + '"\
                                              data-field="contact_address"\
                                              data-key="' + window.lodash.escape(v.key) + '">\
                                              <i class="fi-pencil"></i>\
                                          </button>\
                                          <button type="button" class="button alert input-height delete-button-style channel-delete-button delete-button" title="' + window.lodash.escape(jsObject["mapbox"]["translations"]["delete_location"]) + '" data-id="' + window.lodash.escape(v.key) + '" data-field="contact_address" data-key="' + window.lodash.escape(v.key) + '">&times;</button>\
                                        </div>\
                                      </div>');
                                }
                            });
                        }
                        field_meta.val('');
                    }
                    break;
                }
                default:
                    break;
            }
        });
    };
}

// Call immediately to add methods
ensureSharedFunctionsMethods();

// Also call after DOM ready to re-add methods if shared-functions.js overwrites SHAREDFUNCTIONS
jQuery(document).ready(function() {
    ensureSharedFunctionsMethods();
});
