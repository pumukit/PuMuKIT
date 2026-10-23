import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        mm: String,
        addUrl: String,
        deleteUrl: String,
        childrenUrl: String,
        searchUrl: String,
        reloadUrl: String,
        codes: Boolean,
        refresh: Array,
    };

    select(event) {
        event.preventDefault();
        $(event.currentTarget).siblings('.active').removeClass('active').end().addClass('active');
    }

    toggle(event) {
        const { id, block, cod } = event.params;
        const item = $(event.currentTarget.parentElement);
        if (item.hasClass('notload') && !item.hasClass('searchresult')) {
            item.removeClass('notload');
            const url = this.childrenUrlValue.replace('__tag__', id);
            $.get(`${url}?tag_id=${encodeURIComponent(block)}&mm_id=${encodeURIComponent(this.mmValue)}`, (response) => {
                item.after(response);
                this.highlight(block);
            });
            item.addClass('expanded').removeClass('collapsed');
        } else if (item.hasClass('expanded')) {
            item.removeClass('searchresult');
            $(`.c_${cod}`).hide();
            $(`.c_${cod}.expanded`).removeClass('expanded').addClass('collapsed');
            item.removeClass('expanded').addClass('collapsed');
        } else {
            $(`.tag_li_parent_${id}`).show();
            item.addClass('expanded').removeClass('collapsed');
        }
    }

    add(event) {
        const { id } = event.params;
        $.post(this.addUrlValue.replace('__tag__', encodeURIComponent(id)), { tag: id }, (response) => {
            response.added.forEach((tag) => {
                if (!response.prototype) {
                    this.count(tag.id, 1);
                }
                const path = tag.group.split('|');
                if (path[1] && path.length > 3) {
                    this.append(tag, path[1]);
                }
            });
            this.reloadPreview();
        });
    }

    remove(event) {
        const { id } = event.params;
        $.ajax({ url: this.deleteUrlValue.replace('__tag__', encodeURIComponent(id)), type: 'DELETE' }).done((response) => {
            response.deleted.forEach((tag) => {
                $(`#select_li_${tag.id}`).remove();
                if (!response.prototype) {
                    this.count(tag.id, -1);
                }
            });
            this.reloadPreview();
        });
    }

    addActive(event) {
        event.preventDefault();
        this.dblclick(`#all_tag_${event.params.block} > a.active`);
    }

    removeActive(event) {
        event.preventDefault();
        this.dblclick(`#select_ul_tag_cod_${event.params.cod} > a.active`);
    }

    filter(event) {
        const { block } = event.params;
        const text = event.currentTarget.value.trim();
        clearTimeout(this.filterTimeout);
        $(`#tag_filter_${block}_loader`).css('opacity', '1');
        this.filterTimeout = setTimeout(() => this.search(block, text), 300);
    }

    reset(event) {
        event.preventDefault();
        const { block } = event.params;
        $(`#filter_tags_${block}`).val('').focus();
        this.reload(block);
    }

    search(block, text) {
        if (!this.searchable(text)) {
            this.reload(block);

            return;
        }
        const query = `search_text=${encodeURIComponent(text)}&mmId=${encodeURIComponent(this.mmValue)}&parent=${encodeURIComponent(block)}`;
        $(`#all_tag_${block}`).load(`${this.searchUrlValue}?${query}`, () => {
            $(`#all_tag_${block} .list-group-item.collapsed`).removeClass('collapsed').addClass('searchresult expanded');
            $(`#tag_filter_${block}_loader`).css('opacity', '0');
            this.highlight(block);
        });
    }

    reload(block) {
        const query = `mmId=${encodeURIComponent(this.mmValue)}&parentId=${encodeURIComponent(block)}`;
        $(`#all_tag_${block}`).load(`${this.reloadUrlValue}?${query}`, () => {
            $(`#tag_filter_${block}_loader`).css('opacity', '0');
        });
    }

    highlight(block) {
        const text = String($(`#filter_tags_${block}`).val() ?? '').trim();
        if (!this.searchable(text)) {
            return;
        }
        const needle = text.toLowerCase();
        $(`#all_tag_${block} a span`)
            .removeClass('highlighted')
            .filter((index, span) => span.textContent.toLowerCase().includes(needle))
            .addClass('highlighted');
    }

    searchable(text) {
        return /[0-9a-zà-ü]{3,}/i.test(text);
    }

    append(tag, cod) {
        const item = document.createElement('a');
        item.href = '#';
        item.className = `list-group-item tag-rm-${tag.cod}`;
        item.id = `select_li_${tag.id}`;
        item.dataset.action = `click->${this.identifier}#select dblclick->${this.identifier}#remove`;
        item.setAttribute(`data-${this.identifier}-id-param`, JSON.stringify(tag.id));
        item.textContent = this.codesValue ? `${tag.cod} - ${tag.name}` : tag.name;
        $(`#select_ul_tag_cod_${cod}`).append(item);
    }

    count(id, increment) {
        const badge = $(`#info_num_mm_${id}`);
        if (badge.length) {
            badge.text(parseInt(badge.text(), 10) + increment);
        }
    }

    dblclick(selector) {
        $(selector).each((index, item) => item.dispatchEvent(new MouseEvent('dblclick', { bubbles: true })));
    }

    reloadPreview() {
        this.refreshValue.forEach((item) => $(item.target).load(item.url));
    }
}
