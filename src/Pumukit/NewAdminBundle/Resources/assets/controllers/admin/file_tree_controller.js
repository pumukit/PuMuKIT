import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String, type: String, messages: Object };

    toggle(event) {
        event.preventDefault();
        const base = $(event.currentTarget);
        const hash = base.data('hash');
        if (!base.hasClass('collapsed')) {
            $(`.${hash}`).hide();
            $(`.${hash}`).not('.elem').addClass('collapsed').removeClass('expanded');
            base.addClass('collapsed').removeClass('expanded');

            return;
        }
        base.removeClass('collapsed').addClass('expanded');
        if (!base.hasClass('noload')) {
            $(`.p_${hash}`).show();
            base.addClass('expanded').removeClass('collapsed');

            return;
        }
        const { dir, num } = event.params;
        $.get(`${this.urlValue}?type=${this.typeValue}&dir=${encodeURIComponent(dir)}`, (files) => {
            base.after(Object.values(files).flatMap((file) => this.items(file, base, Number(num))));
            base.removeClass('noload');
        }, 'json').fail((xhr) => {
            const error = xhr.responseJSON?.error;
            window.no_success(error ? `${error.message}: (${error.exception[0].message})` : xhr.statusText);
        });
    }

    select(event) {
        event.preventDefault();
        $(event.currentTarget).siblings('.active').removeClass('active').end().addClass('active');
        $('#server_selected_path').val(event.params.path);
    }

    items(file, base, num) {
        const parentClasses = `${base.data('path')} p_${base.data('hash')}`;
        const entry = this.link(`list-group-item ${file.is_file ? 'elem' : 'collapsed noload'} ${parentClasses}`, num, this.label(file), file.relativepath);
        entry.dataset.hash = file.hash;
        entry.dataset.path = `${base.data('path')} ${file.hash} `;
        if (file.is_file) {
            entry.setAttribute('data-action', 'click->admin--file-tree#select');
            entry.setAttribute('data-admin--file-tree-path-param', file.path);

            return [entry];
        }
        entry.setAttribute('data-action', 'click->admin--file-tree#toggle');
        entry.setAttribute('data-admin--file-tree-dir-param', file.path);
        entry.setAttribute('data-admin--file-tree-num-param', String(num + 1));
        if ('dir' !== this.typeValue && 'both' !== this.typeValue) {
            return [entry];
        }
        const directory = this.link(`list-group-item element ${parentClasses}`, num, this.messagesValue.thisDirectory, null);
        directory.style.color = 'blue';
        directory.style.textDecoration = 'underline';
        directory.setAttribute('data-action', 'click->admin--file-tree#select');
        directory.setAttribute('data-admin--file-tree-path-param', file.path);

        return [entry, directory];
    }

    link(className, num, text, title) {
        const link = document.createElement('a');
        link.href = '#';
        link.className = className;
        const indent = document.createElement('span');
        indent.style.paddingLeft = `${10 * num}px`;
        const icon = document.createElement('i');
        icon.className = 'fa-solid tag_icon';
        indent.append(icon);
        const label = document.createElement('span');
        if (null !== title) {
            label.title = title;
        }
        label.textContent = text;
        link.append(indent, label);

        return link;
    }

    label(file) {
        const name = file.relativepath.length > 50 ? `${file.relativepath.slice(0, 35)}...${file.relativepath.slice(-15)}` : file.relativepath;

        return file.content ? `${name} [${file.content} video(s)]` : name;
    }
}
