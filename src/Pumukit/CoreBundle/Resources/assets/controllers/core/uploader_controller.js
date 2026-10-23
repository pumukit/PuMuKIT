import { Controller } from '@hotwired/stimulus';
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';
import XHRUpload from '@uppy/xhr-upload';
import en_US from '@uppy/locales/lib/en_US.js';
import es_ES from '@uppy/locales/lib/es_ES.js';
import gl_ES from '@uppy/locales/lib/gl_ES.js';
import { trans } from '@pumukit/translator';

const LOCALES = { en: en_US, es: es_ES, gl: gl_ES };
const DEFAULT_PROFILE_SELECTOR = 'input[name="profile_option"]:checked';
const DASHBOARD_REPORTED = ['restriction-failed', 'error'];

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['dashboard', 'input', 'progress', 'progressBar', 'file', 'fileName', 'fileSize', 'submit'];
    static values = { options: Object };

    connect() {
        const options = this.optionsValue;
        this.loadStyles(options.styles ?? []);
        const uppyOptions = {
            debug: Boolean(options.debug),
            autoProceed: Boolean(options.autoProceed),
            allowMultipleUploadBatches: 'xhr' !== options.transport,
            meta: { ...options.meta },
            restrictions: { ...options.restrictions },
        };
        if (LOCALES[options.locale]) {
            uppyOptions.locale = LOCALES[options.locale];
        }
        if (options.renameFiles) {
            uppyOptions.onBeforeFileAdded = (file, files) => this.rename(file, files);
        }

        this.uppy = new Uppy(uppyOptions);

        if ('xhr' === options.transport) {
            this.uppy.use(XHRUpload, {
                endpoint: options.endpoint,
                method: 'post',
                formData: true,
                fieldName: options.xhr.fieldName,
                limit: options.xhr.limit,
                timeout: options.xhr.timeout,
            });
        } else {
            this.uppy.use(Tus, {
                endpoint: options.endpoint,
                overridePatchMethod: Boolean(options.tus.overridePatchMethod),
                limit: options.tus.limit,
            });
        }

        const dashboardTarget = options.dashboard?.target ?? (this.hasDashboardTarget ? this.dashboardTarget : null);
        this.usesDashboard = Boolean(dashboardTarget);
        if (this.usesDashboard) {
            this.uppy.use(Dashboard, {
                inline: true,
                target: dashboardTarget,
                proudlyDisplayPoweredByUppy: false,
            });
        }

        if (this.hasProgressBarTarget && options.progressColor) {
            this.progressBarTarget.style.backgroundColor = options.progressColor;
        }

        this.uppy.on('file-added', (file) => this.fileAdded(file));
        this.uppy.on('file-removed', (file) => this.fileRemoved(file));
        this.uppy.on('upload', () => this.uploadStarted());
        this.uppy.on('progress', (progress) => this.showProgress(progress));
        this.uppy.on('restriction-failed', (file, error) => this.report('restriction-failed', { file, error, message: error.message }));
        this.uppy.on('upload-error', (file, error, response) => this.uploadFailed(file, error, response));
        this.uppy.on('upload-success', (file, response) => this.uploaded(file, response));
        this.uppy.on('complete', (result) => this.dispatch('complete', { detail: { result } }));
    }

    disconnect() {
        this.uppy?.destroy();
        this.uppy = null;
    }

    loadStyles(styles) {
        styles.forEach((href) => {
            if (document.querySelector(`link[rel="stylesheet"][href="${CSS.escape(href)}"]`)) {
                return;
            }
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.append(link);
        });
    }

    select() {
        const files = Array.from(this.inputTarget.files);
        this.inputTarget.value = '';
        if (1 === this.optionsValue.restrictions?.maxNumberOfFiles && files.length) {
            this.uppy.cancelAll();
        }
        files.forEach((file) => {
            try {
                this.uppy.addFile({ source: 'input', name: file.name, type: file.type, data: file });
            } catch {
                return;
            }
        });
    }

    upload(event) {
        event?.preventDefault();
        if (this.optionsValue.formMeta) {
            this.uppy.setMeta(this.formMeta());
        }
        this.uppy.upload().catch(() => {});
    }

    remove(event) {
        event?.preventDefault();
        this.uppy.cancelAll();
    }

    rename(file, files) {
        if (Object.hasOwn(files, file.id)) {
            return false;
        }
        const extension = file.name.split('.').pop();
        const name = `${Date.now()}_${file.name.replace(/[\W_]+/g, '_')}.${extension}`;

        return { ...file, name, meta: { ...file.meta, name } };
    }

    formMeta() {
        const form = this.element instanceof HTMLFormElement ? this.element : this.element.closest('form');
        const meta = {};
        if (!form) {
            return meta;
        }
        for (const [key, value] of new FormData(form)) {
            if ('string' === typeof value) {
                meta[key] = value;
            }
        }

        return meta;
    }

    fileAdded(file) {
        this.showFile(file);
        this.toggleSubmit();
        this.dispatch('added', { detail: { file } });
    }

    fileRemoved(file) {
        this.showFile(this.uppy.getFiles()[0] ?? null);
        this.toggleSubmit();
        this.dispatch('removed', { detail: { file } });
    }

    uploadStarted() {
        if (this.hasProgressTarget) {
            this.progressTarget.hidden = false;
        }
        this.showProgress(0);
    }

    showProgress(progress) {
        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = `${progress}%`;
            this.progressBarTarget.setAttribute('aria-valuenow', String(progress));
        }
    }

    showFile(file) {
        if (this.hasFileTarget) {
            this.fileTarget.hidden = !file;
        }
        if (this.hasFileNameTarget) {
            this.fileNameTarget.textContent = file?.name ?? '';
        }
        if (this.hasFileSizeTarget) {
            this.fileSizeTarget.textContent = file ? `${(file.size / 1024 / 1024).toFixed(2)} MB` : '';
        }
    }

    toggleSubmit() {
        const empty = 0 === this.uppy.getFiles().length;
        this.submitTargets.forEach((button) => {
            button.disabled = empty;
        });
    }

    uploadFailed(file, error, response) {
        if (!this.usesDashboard) {
            this.uppy.cancelAll();
        }
        this.report('error', { file, error, response, message: error.message });
    }

    uploaded(file, response) {
        const options = this.optionsValue;
        const body = 'xhr' === options.transport ? response?.body ?? {} : {};

        if ('failed' === body.uploaded) {
            this.report('failed', { file, response, body, message: body.message ?? '' });

            return;
        }

        this.dispatch('success', { detail: { file, response, body } });

        if (options.import) {
            this.import(file, response);
        }
        if (body.endPage) {
            window.location.href = body.endPage;
        }
        if (body.mmId) {
            window.parent.postMessage({ mmId: body.mmId }, '*');
        }
    }

    async import(file, response) {
        const options = this.optionsValue.import;
        const params = new URLSearchParams({ fileName: file.meta.name, series: file.meta.series ?? '' });
        const profile = document.querySelector(options.profileSelector ?? DEFAULT_PROFILE_SELECTOR)?.value;
        if (undefined !== profile) {
            params.set('profile', profile);
        }
        Object.entries(options.params ?? {}).forEach(([key, value]) => params.set(key, value));
        if (options.sendUuid) {
            params.set('uuid', response?.uploadURL ? response.uploadURL.split('/').pop() : '');
        }

        const result = await this.post(options.url, params);
        if (false === result.ok || false === result.data?.success) {
            this.report('import-failed', { file, result, message: trans('The file was uploaded but could not be imported') });

            return;
        }
        this.dispatch('imported', { detail: { file, result: result.data } });
    }

    async post(url, params) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: params.toString(),
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return { ok: false };
            }
            try {
                return { ok: true, data: await response.json() };
            } catch {
                return { ok: true, data: null };
            }
        } catch {
            return { ok: false };
        }
    }

    report(name, detail) {
        const event = this.dispatch(name, { detail, cancelable: true });
        if (event.defaultPrevented || !detail.message) {
            return;
        }
        if (this.usesDashboard && DASHBOARD_REPORTED.includes(name)) {
            return;
        }
        if (this.usesDashboard) {
            this.uppy.info(detail.message, 'error', 10000);
        } else {
            window.alert(detail.message);
        }
    }
}
