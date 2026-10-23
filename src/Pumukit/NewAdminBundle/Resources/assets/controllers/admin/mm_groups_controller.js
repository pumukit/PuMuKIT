import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['lists', 'remember', 'warning'];
    static values = {
        mm: String,
        template: String,
        updateUrl: String,
        groupsUrl: String,
        lastRelationUrl: String,
        previewUrl: String,
        seriesUrl: String,
        messages: Object,
    };

    changed() {
        this.isLastRelation('', '').done((last) => {
            if (false === last) {
                $(this.warningTarget).hide();
                $(this.rememberTarget).show();
            } else if (true === last) {
                $(this.rememberTarget).hide();
                $(this.warningTarget).show();
            }
        });
    }

    save(event) {
        event.preventDefault();
        $(this.rememberTarget).hide();
        $.ajax({
            url: this.updateUrlValue,
            type: 'POST',
            data: { addGroups: this.ids(`a.add-${this.resource()}-group-item`), deleteGroups: this.ids(`a.delete-${this.resource()}-group-item`) },
        })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                if (1 === response.redirect) {
                    window.location.href = response.url;
                }
                window.success(this.messagesValue.saved);
            });
    }

    reset(event) {
        event.preventDefault();
        $(this.rememberTarget).hide();
        $.ajax({ url: this.groupsUrlValue, type: 'GET' })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                this.dispatch('load', { target: this.listsTarget, prefix: 'admin--group-lists', detail: { add: response.addGroups, delete: response.deleteGroups } });
                $(this.rememberTarget).hide();
            });
    }

    removeOwner(event) {
        event.preventDefault();
        const { url, person, role, name } = event.params;
        this.isLastRelation(this.mmValue, person).done((last) => {
            const question = `${this.messagesValue.confirm} ${name}?`;
            if (false === last && window.confirm(question)) {
                this.deleteRelation(url, role, name);
            } else if (true === last && window.confirm(`${this.messagesValue.noAccess} ${question}`)) {
                this.deleteLastRelation(url, name);
            }
        });
    }

    deleteRelation(url, role, name) {
        $(`#${role}_person_mms${this.templateValue}`).load(url, { _method: 'DELETE' }, () => {
            $('#preview_mm').load(this.previewUrlValue, (responseText, textStatus, xhr) => {
                if (xhr.status >= 400) {
                    window.no_success(`${this.deleteError(name)}${responseText}`);
                } else {
                    window.success(this.messagesValue.deleted);
                }
            });
        });
    }

    deleteLastRelation(url, name) {
        $.ajax({ url, type: 'POST', data: { _method: 'DELETE' } })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${this.deleteError(name)}${errorThrown}: ${xhr.responseText}`))
            .done(() => {
                window.success(this.messagesValue.deleted);
                window.location.href = this.seriesUrlValue;
            });
    }

    deleteError(name) {
        return `${this.messagesValue.deleteError} "${name} from MultimediaObject": `;
    }

    isLastRelation(mmId, personId) {
        const resource = this.resource();

        return $.ajax({
            url: this.lastRelationUrlValue,
            type: 'POST',
            data: { mmId, personId, owners: this.ids(`.${resource}-personal-scope-person-id`), addGroups: this.ids(`a.add-${resource}-group-item`) },
        }).fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`));
    }

    ids(selector) {
        return [...this.element.querySelectorAll(selector)].map((item) => item.id);
    }

    resource() {
        return `mm${this.templateValue}`;
    }
}
