import { Controller } from '@hotwired/stimulus';
import { convertSizeToBytes } from '../../upload.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        target: String,
        endpoint: String,
        series: String,
        locale: String,
        debug: Boolean,
        minFileSize: String,
        maxFileSize: String,
        maxNumberOfFiles: Number,
        allowedFileTypes: Array,
        overridePatchMethod: Boolean,
        limit: Number,
        naked: Boolean,
        importUrl: String,
        importParams: Object,
    };

    connect() {
        const locales = {
            es: Uppy.locales.es_ES,
            en: Uppy.locales.en_US,
            gl: Uppy.locales.gl_ES,
        };

        this.uppy = Uppy.Core({
            debug: this.debugValue,
            autoProceed: true,
            locale: locales[this.localeValue],
            meta: {
                series: this.seriesValue,
            },
            restrictions: {
                minFileSize: convertSizeToBytes(this.minFileSizeValue),
                maxFileSize: convertSizeToBytes(this.maxFileSizeValue),
                maxNumberOfFiles: this.maxNumberOfFilesValue,
                allowedFileTypes: this.allowedFileTypesValue,
            },
            onBeforeFileAdded: (currentFile) => {
                const extension = currentFile.name.split('.').pop();
                const fileName = currentFile.name.replace(/[\W_]+/g, '_');
                currentFile.name = `${Date.now()}_${fileName}.${extension}`;
            },
        })
            .use(Uppy.Dashboard, {
                proudlyDisplayPoweredByUppy: false,
                target: this.targetValue,
                inline: true,
            })
            .use(Uppy.Tus, {
                endpoint: this.endpointValue,
                overridePatchMethod: this.overridePatchMethodValue,
                limit: this.limitValue,
            })
            .run();

        this.uppy.on('upload-success', (file, response) => {
            const uploadURL = response?.uploadURL || response?.url;
            if (!uploadURL) {
                return;
            }

            const uuid = uploadURL.split('/').pop();
            this.uppy.setFileState(file.id, { tusUuid: uuid });
            file.tusUuid = uuid;

            this.sendImportRequest(file);
        });
    }

    disconnect() {
        this.uppy?.close();
    }

    sendImportRequest(file) {
        const body = new URLSearchParams({
            fileName: file.name,
            series: this.seriesValue,
            profile: document.querySelector('input[name="profile_option"]:checked').value,
            ...this.importParamsValue,
        });
        if (this.nakedValue) {
            body.append('uuid', file.tusUuid || '');
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', this.importUrlValue, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(body.toString());
    }
}
