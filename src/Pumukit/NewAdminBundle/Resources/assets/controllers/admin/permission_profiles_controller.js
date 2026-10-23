import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { saveUrl: String, messages: Object };

    changed() {
        $('#remember_save_permissionprofile').show();
    }

    cancel() {
        $('#pumukitnewadmin_permissionprofile_table_form')[0].reset();
        $('#remember_save_permissionprofile').hide();
    }

    save(event) {
        event.preventDefault();
        $('#remember_save_permissionprofile').hide();
        const selectedDefault = $('input.pumukitnewadmin_permissionprofile_default:checked').last().attr('value') ?? '';
        const selectedScopes = $('select.permissionprofile_scope:not([disabled]) option:selected').map((index, option) => $(option).attr('value')).get();
        const checkedPermissions = $('.permission_permissionprofile_checkbox:checked:not([disabled])').map((index, input) => input.id).get();

        $.ajax({
            url: this.saveUrlValue,
            type: 'POST',
            data: {
                selected_default: selectedDefault,
                selected_scopes: JSON.stringify(selectedScopes),
                checked_permissions: JSON.stringify(checkedPermissions),
            },
        })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                $('#list_permissionprofiles').html(response);
                $('[data-toggle="tooltip"]').tooltip();
                window.success(this.messagesValue.saved);
            });
    }

    toggle(event) {
        const element = event.currentTarget;
        if (element.disabled) {
            return;
        }
        const profileId = element.id.split('__')[1];
        const scope = $(`select#pumukitnewadmin_permissionprofile_scope_${profileId} option:selected`).attr('value');
        if (element.checked) {
            this.dependencies(element, scope).forEach((dependency) => $(`#${dependency}`).prop('checked', 'checked'));
        } else {
            this.uncheckDependents(profileId, scope, element.id);
            this.fixDependencies(profileId, scope);
        }
    }

    changeScope(event) {
        const profileId = event.params.id;
        const scopeId = $(`#pumukitnewadmin_permissionprofile_scope_${profileId} option:selected`).val();
        const scope = scopeId.split('__')[0];
        $(`input[id$=__${profileId}__permission]`).each((index, permission) => {
            const $permission = $(permission);
            const wrapper = $permission.closest('div');
            if ('ROLE_SCOPE_NONE' === scope) {
                $permission.attr('disabled', 'disabled').removeAttr('checked');
                wrapper.attr('class', 'checkbox pmk_disabled_checkbox');
                this.profilePermissions(profileId).each((i, checked) => $(`#${checked.id}`).prop('checked', ''));
            } else {
                $permission.removeAttr('disabled');
                if ('checkbox pmk_disabled_checkbox' === wrapper.attr('class')) {
                    wrapper.attr('class', 'checkbox');
                }
                this.fixDependencies(profileId, scopeId);
            }
        });
    }

    dependencies(element, scope) {
        const raw = scope.indexOf('GLOBAL') > -1 ? element.getAttribute('data-dependencies-global') : element.getAttribute('data-dependencies-personal');

        return (raw ?? '').split(' ');
    }

    profilePermissions(profileId) {
        return $(`[id^='ROLE_'][id$=__${profileId}__permission]:checked`);
    }

    description(permission) {
        return $(`#${permission}`).html().replace(/^\s\s*/, '').replace(/\s\s*$/, '').split(' <i')[0];
    }

    uncheckDependents(profileId, scope, fullPermission) {
        const dependents = [];
        this.profilePermissions(profileId).each((index, element) => {
            if (this.dependencies(element, scope).includes(fullPermission)) {
                dependents.push(element.id);
            }
        });
        if (0 === dependents.length) {
            return;
        }
        const names = dependents.map((id) => `"${this.description(id.split('__')[0])}"`).join(', ');
        const permission = `"${this.description(fullPermission.split('__')[0])}"`;
        const { permission: prefix, dependent, confirm } = this.messagesValue;
        if (window.confirm(`${prefix} ${permission} ${dependent}: ${names}. ${confirm}`)) {
            dependents.forEach((id) => $(`#${id}`).prop('checked', ''));
        }
    }

    fixDependencies(profileId, scope) {
        this.profilePermissions(profileId).each((index, element) => {
            this.dependencies(element, scope).forEach((dependency) => {
                if (!$(`#${dependency}`).prop('checked')) {
                    $(`#${dependency}`).prop('checked', 'checked');
                }
            });
        });
    }
}
