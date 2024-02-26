<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType\Metadata;

final class VideoAudio implements MediaMetadata
{
    private string $metadata;

    private function __construct(?string $metadata)
    {
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
        ];
    }

    public function toString(): string
    {
        return $this->metadata() ?? '';
    }

    public function metadata(): ?string
    {
        return $this->metadata;
    }

    public static function create(?string $metadata): VideoAudio
    {
        return new self($metadata);
    }

    public function duration(): int
    {
        $metadata = $this->decodeMetadataInfo();

        return (int) ceil((float) $metadata->format->duration) ?? 0;
    }

    public function isOnlyAudio(): bool
    {
        $audioStream = $this->audioStreamInfo();
        $videoStream = $this->videoStreamInfo();
        if ($audioStream && !$videoStream) {
            return true;
        }

        return false;
    }

    public function codecName(): ?string
    {
        $stream = $this->videoStreamInfo();
        if (!$stream) {
            return null;
        }

        return $stream->codec_name;
    }

    public function numFrames(): int
    {
        if (!$this->isOnlyAudio()) {
            return $this->frameNumber($this->duration());
        }

        return 0;
    }

    public function size(): int
    {
        $metadata = $this->decodeMetadataInfo();

        return $metadata->size ?? 0;
    }

    public function width(): ?int
    {
        $stream = $this->videoStreamInfo();
        if (!$stream) {
            return null;
        }

        return $stream->width;
    }

    public function height(): ?int
    {
        $stream = $this->videoStreamInfo();
        if (!$stream) {
            return null;
        }

        return $stream->height;
    }

    public function timeOfaFrame(int $frame): float
    {
        if (!$this->frameRate()) {
            return 0;
        }

        if (str_contains($this->frameRate(), '/')) {
            $aux = explode('/', $this->frameRate());

            return (float) ($frame * (int) $aux[1] / (int) $aux[0]);
        }

        return (float) ($frame / $this->frameRate());
    }

    private function frameNumber(int $duration): int
    {
        $frameRate = $this->frameRate();

        if (str_contains($frameRate, '/')) {
            $aux = explode('/', $frameRate);

            return (int) ($duration * (int) $aux[0] / (int) $aux[1]);
        }

        return (int) ($duration * $frameRate);
    }

    private function frameRate(): ?string
    {
        $stream = $this->videoStreamInfo();
        if (!$stream) {
            return null;
        }

        return $stream->avg_frame_rate;
    }

    private function videoStreamInfo()
    {
        foreach ($this->decodeMetadataInfo()->streams as $stream) {
            if (isset($stream->codec_type) && 'video' === (string) $stream->codec_type) {
                return $stream;
            }
        }

        return null;
    }

    private function audioStreamInfo()
    {
        foreach ($this->decodeMetadataInfo()->streams as $stream) {
            if (isset($stream->codec_type) && 'audio' === (string) $stream->codec_type) {
                return $stream;
            }
        }

        return null;
    }

    private function decodeMetadataInfo()
    {
        return json_decode($this->metadata(), false, 512, JSON_THROW_ON_ERROR);
    }
}
