import { Controller } from '@hotwired/stimulus';
import { trans } from '@pumukit/translator';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['poster'];
    static values = {
        state: String,
        player: String,
        textColor: String,
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
        clearInterval(this.timer);
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
        $(this.playerValue).find('.warning').css('color', this.textColorValue);
    }

    startCountdown(selector, date, showTime) {
        const element = document.querySelector(selector);
        const end = new Date(date).getTime();
        const days = trans('days');
        const pad = (number) => String(number).padStart(2, '0');
        const tick = () => {
            const seconds = Math.max(0, Math.ceil((end - Date.now()) / 1000));
            if (showTime && element) {
                element.textContent = `${pad(Math.floor(seconds / 86400))} ${days} ${Math.floor(seconds / 3600) % 24}:${pad(Math.floor(seconds / 60) % 60)}:${pad(seconds % 60)}`;
            }
            if (seconds === 0) {
                clearInterval(this.timer);
                window.location.reload();
            }
        };

        clearInterval(this.timer);
        this.timer = setInterval(tick, 1000);
        tick();
    }
}
