import { Controller } from '@hotwired/stimulus';
import { trans } from '@pumukit/translator';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['poster'];
    static values = {
        state: String,
        player: String,
        nextSessionStarts: String,
        secondsToEvent: Number,
        nowSessionEnds: String,
        nowSessionDuration: Number,
    };

    connect() {
        if (this.stateValue === 'future') {
            this.future();
        } else if (this.stateValue === 'now') {
            this.now();
        } else {
            this.finished();
        }
    }

    disconnect() {
        $(this.countdown).countdown('remove');
    }

    future() {
        let date = new Date(parseInt(this.nextSessionStartsValue));
        if (this.secondsToEventValue) {
            date = new Date(new Date().getTime() + this.secondsToEventValue + 1000 * Math.floor(Math.random() * 60));
        }
        if (date < new Date()) {
            window.location.reload();
        }

        this.showPoster();
        this.startCountdown('#warning', date, true);
    }

    now() {
        let date = new Date(parseInt(this.nowSessionEndsValue));
        if (this.nowSessionDurationValue) {
            date = new Date(new Date().getTime() + this.nowSessionDurationValue);
        }

        this.startCountdown('#countdownClock', date, true);
    }

    finished() {
        this.showPoster();
        const date = new Date();
        this.startCountdown('#warning', date.setMinutes(date.getMinutes() + 1), false);
    }

    showPoster() {
        $(this.playerValue).html(this.posterTarget.innerHTML);
    }

    startCountdown(selector, date, showTime) {
        const days = trans('days');
        this.countdown = $(selector);
        this.countdown
            .countdown(date, function (event) {
                if (showTime) {
                    $(this).text(event.strftime('%D ' + days + ' %-H:%M:%S'));
                }
            })
            .on('finish.countdown', () => window.location.reload());
    }
}
