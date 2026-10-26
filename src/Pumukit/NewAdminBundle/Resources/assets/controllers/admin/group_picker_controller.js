import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['available', 'current'];
    static values = {
        updateUrl: String,
        groupsUrl: String,
        target: String,
        localOrigin: String,
        showCodes: Boolean,
        messages: Object,
    };

    select(event) {
        event.preventDefault();
        if ('1' !== event.currentTarget.dataset.editable) {
            return;
        }
        $(event.currentTarget).siblings('.active').removeClass('active').end().addClass('active');
    }

    toggle(event) {
        event.preventDefault();
        this.move(event.currentTarget);
    }

    moveFromCurrent(event) {
        event.preventDefault();
        this.currentTarget.querySelectorAll(':scope > a.active').forEach((item) => this.move(item));
    }

    moveFromAvailable(event) {
        event.preventDefault();
        this.availableTarget.querySelectorAll(':scope > a.active').forEach((item) => this.move(item));
    }

    move(item) {
        if ('1' !== item.dataset.editable) {
            window.alert(item.dataset.message ?? '');

            return;
        }
        const toCurrent = this.availableTarget.contains(item);
        item.classList.remove('active', 'notload', 'add-group-item', 'delete-group-item');
        item.classList.add(toCurrent ? 'add-group-item' : 'delete-group-item');
        item.id = `${toCurrent ? 'add' : 'delete'}_group_${item.dataset.groupId}`;
        (toCurrent ? this.currentTarget : this.availableTarget).append(item);
    }

    save(event) {
        event.preventDefault();
        $('#myModal').modal('hide');
        const ids = (selector) => [...this.element.querySelectorAll(selector)].map((item) => item.id);
        $.ajax({
            url: this.updateUrlValue,
            type: 'POST',
            data: { addGroups: ids('a.add-group-item'), deleteGroups: ids('a.delete-group-item') },
        })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                $(this.targetValue).html(response);
                window.success(this.messagesValue.success);
            });
    }

    reset(event) {
        event.preventDefault();
        $.ajax({ url: this.groupsUrlValue, type: 'GET' })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                this.currentTarget.replaceChildren(...Object.entries(response.add).map(([id, group]) => this.item('add', id, group, response.userOrigin)));
                this.availableTarget.replaceChildren(...Object.entries(response.delete).map(([id, group]) => this.item('delete', id, group, response.userOrigin)));
            });
    }

    item(list, id, group, userOrigin) {
        const editable = group.origin === this.localOriginValue || userOrigin === this.localOriginValue;
        const item = document.createElement('a');
        item.href = '#';
        item.id = `${list}_group_${id}`;
        item.className = `list-group-item pumukitnewadmin-trim-row group_${group.key} ${editable ? `${list}-group-item` : 'disabled-group-item'}`;
        item.dataset.groupId = id;
        item.dataset.editable = editable ? '1' : '0';
        item.dataset.message = this.messagesValue.external ?? '';
        item.dataset.action = 'click->admin--group-picker#select dblclick->admin--group-picker#toggle';
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-users';
        item.append(icon, ' ', this.showCodesValue ? `${group.key} - ${group.name} - (${group.origin})` : group.name);

        return item;
    }
}
