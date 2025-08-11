console.log('field-helper.js');

if (typeof window.SHAREDFUNCTIONS === 'undefined') {
    window.SHAREDFUNCTIONS = {
        formatDate: function(timestamp) {
            return new Date(timestamp * 1000).toLocaleDateString();
        },
        formatComment: function(comment) {
            return comment; // Simple implementation
        },
        addLink: function(event) {
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
        }
    };
}

/**
 * Link field event handlers
 */
// Handle add link option clicks
jQuery(document).on('click', '.add-link__option', function(event) {
    window.SHAREDFUNCTIONS.addLink(event);
    jQuery(event.target).parent().hide();
    setTimeout(() => {
        event.target.parentElement.removeAttribute('style');
    }, 100);
});

// Handle link delete button clicks
jQuery(document).on('click', '.link-delete-button', function() {
    jQuery(this).closest('.link-section').remove();
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

// Collect DT and custom fields from the Single Record UI
if (!window.SHAREDFUNCTIONS.collectFields) {
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
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value');
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
                        let deleted = field_meta.val() ? JSON.parse(field_meta.val()) : [];
                        if (rawValue) {
                            try {
                                const parsed = JSON.parse(rawValue);
                                if (Array.isArray(parsed)) {
                                    parsed.forEach(function (item) {
                                        if (item && item.value && String(item.value).trim() !== '') {
                                            values.push({ value: String(item.value).trim() });
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
                            deleted: deleted
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
                                        if (selectedKey && String(selectedKey).trim() !== '') {
                                            options.push({ value: String(selectedKey).trim(), delete: false });
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
                    case 'link': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value') || jQuery(tr).find(selector).val();
                        if (rawValue && String(rawValue).trim() !== '') {
                            try {
                                const parsed = JSON.parse(rawValue);
                                payloadFields.dt.push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: parsed
                                });
                            } catch (e) {
                                payloadFields.dt.push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: String(rawValue).trim()
                                });
                            }
                        }
                        break;
                    }
                    case 'tags': {
                        const dtComponent = jQuery(tr).find('[id="' + field_id + '"]');
                        let rawValue = dtComponent.attr('value');
                        let values = [];
                        if (rawValue) {
                            try {
                                const parsed = JSON.parse(rawValue);
                                if (Array.isArray(parsed)) {
                                    parsed.forEach(function (tagName) {
                                        if (tagName && String(tagName).trim() !== '') {
                                            values.push({ name: String(tagName).trim() });
                                        }
                                    });
                                }
                            } catch (e) {}
                        }
                        if (values.length === 0) {
                            let typeahead = window.Typeahead['.js-typeahead-' + field_id];
                            if (typeahead) {
                                payloadFields.dt.push({
                                    id: field_id,
                                    dt_type: field_type,
                                    template_type: field_template_type,
                                    value: typeahead.items,
                                    deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
                                });
                                break;
                            }
                        }
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: values
                        });
                        break;
                    }
                    case 'location': {
                        let typeahead = window.Typeahead['.js-typeahead-' + field_id];
                        if (typeahead) {
                            payloadFields.dt.push({
                                id: field_id,
                                dt_type: field_type,
                                template_type: field_template_type,
                                value: typeahead.items,
                                deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
                            });
                        }
                        break;
                    }
                    case 'location_meta': {
                        payloadFields.dt.push({
                            id: field_id,
                            dt_type: field_type,
                            template_type: field_template_type,
                            value: (window.selected_location_grid_meta !== undefined) ? window.selected_location_grid_meta : '',
                            deletions: field_meta.val() ? JSON.parse(field_meta.val()) : []
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
            let type = jQuery(entry).data('type');
            if (jQuery(entry).val()) {
                let existingField = payloadFields.dt.find(function (f) { return f.id === fieldKey; });
                if (!existingField) {
                    existingField = { id: fieldKey, dt_type: 'link', template_type: 'dt', value: { values: [] } };
                    payloadFields.dt.push(existingField);
                }
                if (!existingField.value.values) {
                    existingField.value = { values: [] };
                }
                existingField.value.values.push({ value: jQuery(entry).val(), type: type });
            }
        });

        // Handle custom fields (create-record pattern)
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
}