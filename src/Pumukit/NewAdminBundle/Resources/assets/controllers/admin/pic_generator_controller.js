import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['video', 'image', 'buttons', 'frames', 'canvas', 'size', 'format', 'quality', 'qualityBox', 'advanced', 'stream'];

    static values = { width: Number, height: Number, generateUrl: String, mmId: String, listUrl: String, listTarget: String, message: String };

    connect() {
        this.initialSrc = this.imageTarget.src;
        this.drawing = this.canvasTarget.getContext('2d');
        this.ready = () => this.prepare();
        if (4 === this.videoTarget.readyState) {
            this.prepare();
        } else {
            this.videoTarget.addEventListener('canplaythrough', this.ready);
        }
    }

    disconnect() {
        this.videoTarget.removeEventListener('canplaythrough', this.ready);
        this.stopBurst();
    }

    prepare() {
        $(this.buttonsTarget).show();
        this.canvasTarget.width = this.widthValue;
        this.canvasTarget.height = this.heightValue;
        this.imageTarget.height = this.videoTarget.clientHeight;
    }

    previousFrame() {
        this.videoTarget.currentTime -= 0.25;
    }

    nextFrame() {
        this.videoTarget.currentTime += 0.25;
    }

    take() {
        this.select(this.capture().src, true);
    }

    toggleAdvanced() {
        $(this.advancedTarget).toggle();
    }

    showStream(event) {
        this.videoTarget.src = event.params.url;
        this.streamTargets.forEach((button) => button.classList.toggle('btn-primary', button === event.currentTarget));
    }

    changeFormat(event) {
        event.preventDefault();
        this.qualityBoxTarget.style.display = 'png' === this.formatTarget.value ? 'none' : 'inline-block';
    }

    burst(event) {
        event.preventDefault();
        const video = this.videoTarget;
        const total = Number(event.params.num);
        const interval = Math.ceil(parseInt(video.duration, 10)) / total;
        this.framesTarget.querySelectorAll('.new_img').forEach((frame) => frame.remove());
        this.drawing.clearRect(0, 0, this.canvasTarget.width, this.canvasTarget.height);
        this.stopBurst();
        let taken = 0;
        this.burstListener = () => {
            if (video.seeking) {
                return;
            }
            this.capture();
            video.currentTime += interval;
            taken += 1;
            if (video.currentTime >= video.duration - interval || taken >= total) {
                this.stopBurst();
            }
        };
        video.addEventListener('seeked', this.burstListener);
        video.currentTime = 0;
    }

    stopBurst() {
        if (this.burstListener) {
            this.videoTarget.removeEventListener('seeked', this.burstListener);
            this.burstListener = null;
        }
    }

    upload(event) {
        event.preventDefault();
        const reader = new FileReader();
        reader.onload = (loaded) => {
            this.imageTarget.src = loaded.target.result;
            this.addFrame(loaded.target.result, false);
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    addUrl(event) {
        event.preventDefault();
        const frame = this.frame(event.target.value, false);
        frame.addEventListener('load', () => {
            if (frame.width > 0) {
                frame.width = 120;
                this.framesTarget.appendChild(frame);
            }
        });
    }

    submit(event) {
        event.preventDefault();
        const data = this.imageTarget.src;
        if (data === this.initialSrc) {
            $('#myModal').modal('hide');

            return;
        }
        $.post(this.generateUrlValue, { img: data, mm: this.mmIdValue }, () => {
            window.success(this.messageValue);
            $(this.listTargetValue).load(this.listUrlValue);
            $('#myModal').modal('hide');
        });
    }

    capture() {
        const canvas = this.canvasTarget;
        const video = this.videoTarget;
        this.drawing.clearRect(0, 0, canvas.width, canvas.height);
        this.drawing.fillStyle = 'black';
        this.drawing.fillRect(0, 0, canvas.width, canvas.height);
        const videoAspect = video.videoWidth / video.videoHeight;
        const canvasAspect = canvas.width / canvas.height;
        let width = canvas.height * videoAspect;
        let height = canvas.height;
        if (videoAspect > canvasAspect) {
            width = canvas.width;
            height = canvas.width / videoAspect;
        }
        this.drawing.drawImage(video, 0, 0, video.videoWidth, video.videoHeight, (canvas.width - width) / 2, (canvas.height - height) / 2, width, height);

        return this.addFrame(canvas.toDataURL(`image/${this.formatTarget.value}`, parseFloat(this.qualityTarget.value)), true);
    }

    addFrame(src, withSize) {
        const frame = this.frame(src, withSize);
        frame.width = 120;
        this.framesTarget.appendChild(frame);

        return frame;
    }

    frame(src, withSize) {
        const frame = new Image();
        frame.classList.add('new_img', 'btn');
        frame.style.padding = '0px';
        frame.src = src;
        frame.addEventListener('click', () => this.select(frame.src, withSize));

        return frame;
    }

    select(src, withSize) {
        this.imageTarget.src = src;
        if (withSize) {
            this.updateSize();
        }
    }

    updateSize() {
        const src = this.imageTarget.src;
        const size = atob(src.substr('png' === src.substr(11, 3) ? 22 : 23)).length;
        const text = size < 3000 ? `${size} B` : `${Math.ceil(size / 1024)} KB`;
        this.sizeTarget.textContent = `${this.canvasTarget.width}x${this.canvasTarget.height} - ${text}`;
    }
}
