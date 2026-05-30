EncoderBundle configuration
===========================

Configuration:

```
pumukit_encoder:
  delete_inbox_files: false
  max_execution_job_seconds: 86400
  thumbnail:
    width: 768
    height: 432
  cpus:
    local:
      host: 127.0.0.1
  profiles:
    master_copy:
      display: false
      wizard: true
      master: true
      tags: copy
      resolution_hor: 0
      resolution_ver: 0
      audio: false
      bat: 'cp "{{input}}" "{{output}}"'
      streamserver:
        name: Localmaster
        dir_out: "%pumukit.masters%"
    master_video_h264:
      display: false
      wizard: true
      master: true
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 15 -threads 0 "{{output}}"
      streamserver:
        name: Localmaster
        dir_out: "%pumukit.masters%"
    broadcastable_master:
      display: true
      wizard: true
      master: true
      target: PUCHWEBTV
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 22 -movflags faststart -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
    video_h264:
      display: true
      wizard: true
      master: false
      tags: html5 podcast
      target: PUCHWEBTV PUCHPODCAST
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 22 -movflags faststart -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
    audio_aac:
      display: true
      wizard: true
      master: false
      tags: html5 audio podcast
      target: PUCHWEBTV PUCHPODCAST*
      extension: m4a
      resolution_hor: 0
      resolution_ver: 0
      audio: true
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vn -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
    sbs:
      display: true
      wizard: false
      master: false
      tags: html5, sbs, podcast
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      audio: false
      bat: |
        ffmpeg -i {{ properties.opencastinvert ? tracks_video['presenter/delivery'] : tracks_video['presentation/delivery']  }}  -i {{ properties.opencastinvert ? tracks_video['presentation/delivery'] : tracks_video['presenter/delivery']  }} -filter_complex "[0:v]scale=640:-1[a],[a]pad=1280:720:0:120+((480-in_h)/2) [bg],[1:v]scale=640:-1[b],[bg][b]overlay=w:120+((480-h)/2)" -r 25 -vcodec libx264 -preset medium -crf 22 -maxrate 1100k -bufsize 1835k -acodec aac -ac 2 -ar 44100 -b:a 128k -f mp4 -y "{{output}}"
      streamserver:
        name: Localhost
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
  target_default_profiles:
    PUCHWEBTV:
      audio: "audio_aac"
      video: "video_h264"
    PUCHPODCAST:
      audio: "audio_aac"
      video: "video_h264 audio_aac"
```


##### Global configuration

* `delete_inbox_files` Delete imported inbox files

##### Thubmnails configuration

* `thumbnail` `width` Width resolution of thumbnail
* `thumbnail` `height` Height resolution of thumbnail


##### CPUS configuration

{cpuName} - Name of the CPU

* `cpus` `{cpuName}` `host` Encoder Hostnames (or IPs)
* `cpus` `{cpuName}` `max` Top for the maximum number of concurrent encoding jobs
* `cpus` `{cpuName}` `type` Type of the encoder host (linux, windows or gstreamer). Accepted values: [CpuService::TYPE_LINUX, CpuService::TYPE_WINDOWS, CpuService::TYPE_GSTREAMER]
* `cpus` `{cpuName}` `user` Specifies the user to log in as on the remote encoder host
* `cpus` `{cpuName}` `password` Specifies the password to log in as on the remote encoder host
* `cpus` `{cpuName}` `profiles` Array of profiles. If set, only the profiles listed will be transcoded here

##### Profiles configuration

{profileName} - Profile name
{streamserverName} - StreamServer name

* `profiles` `{profileName}` `generate_pic` When false, mmobj pics will not be generated from tracks generated using this profile
* `profiles` `{profileName}` `nocheckduration` When true, the usual duration checks are not performed on this profile.
* `profiles` `{profileName}` `display` Displays the track
* `profiles` `{profileName}` `wizard` Shown in wizard
* `profiles` `{profileName}` `master` The track is master copy
* `profiles` `{profileName}` `downloadable` The track generated is downloadable
* `profiles` `{profileName}` `target` Profile is used to generate a new track when a multimedia object is tagged with a publication channel tag name with this value. List of names
* `profiles` `{profileName}` `tags` Tags used in tracks created with this profiles
* `profiles` `{profileName}` `extension` Extension of the track. If empty the input file extension is used.
* `profiles` `{profileName}` `resolution_hor` Horizontal resolution of the track, 0 if it depends from original video
* `profiles` `{profileName}` `resolution_ver` Vertical resolution of the track, 0 if it depends from original video
* `profiles` `{profileName}` `audio` The track is only audio
* `profiles` `{profileName}` `bat` Command line to execute transcodification of track. Available variables: {{input}}, {{output}}, {{tmpfile1}}, {{tmpfile2}}, ... {{tmpfile9}}.
* `profiles` `{profileName}` `streamserver` Streamserver output paths for transcodification results
* `profiles` `{profileName}` `streamserver` `{streamserverName}`
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `name` Name of the streamserver
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `dir_out` Directory path of resulting track
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `url_out` URL of resulting track
