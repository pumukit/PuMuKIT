import { Controller } from '@hotwired/stimulus';

const MIN_LENGTH = 20;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['series', 'multimediaObject'];

    connect() {
        const breadcrumb = $(this.element);
        const series = $(this.hasSeriesTarget ? this.seriesTarget : []);
        const multimediaObject = $(this.hasMultimediaObjectTarget ? this.multimediaObjectTarget : []);
        if (!series.length && !multimediaObject.length) {
            return;
        }

        const itemHeight = breadcrumb.find('li').first().height() + 8;
        let breadcrumbHeight = breadcrumb.height();
        let seriesHeight = series.height();
        let appendSeries = false;
        let appendMultimediaObject = false;
        while (seriesHeight + 3 > itemHeight || breadcrumbHeight > itemHeight) {
            if (series.text().trim().length > MIN_LENGTH) {
                series.text(series.text().substring(0, series.text().trim().length - 1));
                appendSeries = true;
            } else {
                multimediaObject.text(multimediaObject.text().substring(0, multimediaObject.text().trim().length - 1));
                if (multimediaObject.text().trim().length < MIN_LENGTH) {
                    break;
                }
                appendMultimediaObject = true;
            }
            seriesHeight = breadcrumb.height();
            breadcrumbHeight = breadcrumb.height();
        }
        if (appendMultimediaObject) {
            multimediaObject.append('...');
        }
        if (appendSeries) {
            series.append('...');
        }
    }
}
