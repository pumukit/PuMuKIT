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
      framerate: 0
      channels: 1
      audio: false
      bat: 'cp "{{input}}" "{{output}}"'
      streamserver:
        name: Localmaster
        type: store
        host: 127.0.0.1
        description: Local master server
        dir_out: "%pumukit.masters%"
      app: cp
      rel_duration_size: 1
      rel_duration_trans: 1
    master_video_h264:
      display: false
      wizard: true
      master: true
      format: mp4
      codec: h264
      mime_type: 'video/x-mp4'
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      bitrate: 1 Mbps
      framerate: 25
      channels: 1
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 15 -threads 0 "{{output}}"
      streamserver:
        name: Localmaster
        type: store
        host: 127.0.0.1
        description: Local master server
        dir_out: "%pumukit.masters%"
      app: ffmpeg
      rel_duration_size: 1
      rel_duration_trans: 1
    broadcastable_master:
      display: true
      wizard: true
      master: true
      target: PUCHWEBTV
      format: mp4
      codec: h264
      mime_type: 'video/x-mp4'
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      bitrate: 1 Mbps
      framerate: 25
      channels: 1
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 22 -movflags faststart -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        type: download
        host: 127.0.0.1
        description: Local download server
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
      app: ffmpeg
      rel_duration_size: 1
      rel_duration_trans: 1
    video_h264:
      display: true
      wizard: true
      master: false
      tags: html5 podcast
      target: PUCHWEBTV PUCHPODCAST
      format: mp4
      codec: h264
      mime_type: 'video/x-mp4'
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      bitrate: 1 Mbps
      framerate: 25
      channels: 1
      audio: false
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vcodec libx264 -preset slow -crf 22 -movflags faststart -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        type: download
        host: 127.0.0.1
        description: Local download server
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
      app: ffmpeg
      rel_duration_size: 1
      rel_duration_trans: 1
    audio_aac:
      display: true
      wizard: true
      master: false
      tags: html5 audio podcast
      target: PUCHWEBTV PUCHPODCAST*
      format: mp4
      codec: aac
      mime_type: 'audio/x-mp4'
      extension: m4a
      resolution_hor: 0
      resolution_ver: 0
      bitrate: 1 Mbps
      framerate: 0
      channels: 1
      audio: true
      bat: |
        ffmpeg -y -i "{{input}}" -acodec aac -vn -threads 0 "{{output}}"
      streamserver:
        name: Localhost
        type: download
        host: 127.0.0.1
        description: Local download server
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
      app: ffmpeg
      rel_duration_size: 1
      rel_duration_trans: 1
    sbs:
      display: true
      wizard: false
      master: false
      tags: html5, sbs, podcast
      format: mp4
      codec: aac
      mime_type: 'video/x-mp4'
      extension: mp4
      resolution_hor: 0
      resolution_ver: 0
      bitrate: 1 Mbps
      framerate: 0
      channels: 1
      audio: false
      bat: |
        ffmpeg -i {{ properties.opencastinvert ? tracks_video['presenter/delivery'] : tracks_video['presentation/delivery']  }}  -i {{ properties.opencastinvert ? tracks_video['presentation/delivery'] : tracks_video['presenter/delivery']  }} -filter_complex "[0:v]scale=640:-1[a],[a]pad=1280:720:0:120+((480-in_h)/2) [bg],[1:v]scale=640:-1[b],[bg][b]overlay=w:120+((480-h)/2)" -r 25 -vcodec libx264 -preset medium -crf 22 -maxrate 1100k -bufsize 1835k -acodec aac -ac 2 -ar 44100 -b:a 128k -f mp4 -y "{{output}}"
      streamserver:
        name: Localhost
        type: download
        host: 127.0.0.1
        description: Local download server
        dir_out: "%pumukit.downloads%"
        url_out: "/storage/downloads"
      app: ffmpeg
      rel_duration_size: 1
      rel_duration_trans: 1
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
* `profiles` `{profileName}` `format` Format of the track
* `profiles` `{profileName}` `codec` Codec of the track
* `profiles` `{profileName}` `mime_type` Mime Type of the track
* `profiles` `{profileName}` `extension` Extension of the track. If empty the input file extension is used.
* `profiles` `{profileName}` `resolution_hor` Horizontal resolution of the track, 0 if it depends from original video
* `profiles` `{profileName}` `resolution_ver` Vertical resolution of the track, 0 if it depends from original video
* `profiles` `{profileName}` `bitrate` Bit rate of the track
* `profiles` `{profileName}` `framerate` Framerate of the track
* `profiles` `{profileName}` `channels` Available Channels
* `profiles` `{profileName}` `audio` The track is only audio
* `profiles` `{profileName}` `bat` Command line to execute transcodification of track. Available variables: {{input}}, {{output}}, {{tmpfile1}}, {{tmpfile2}}, ... {{tmpfile9}}.
* `profiles` `{profileName}` `file_cfg` Configuration file
* `profiles` `{profileName}` `streamserver` Type of streamserver for transcodification and data
* `profiles` `{profileName}` `streamserver` `{streamserverName}`
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `type` Streamserver type. Values accepted: [ProfileService::STREAMSERVER_STORE, ProfileService::STREAMSERVER_DOWNLOAD, ProfileService::STREAMSERVER_WMV, ProfileService::STREAMSERVER_FMS, ProfileService::STREAMSERVER_RED5, ]
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `host` Streamserver Hostname (or IP)
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `description` Streamserver Hostname (or IP)
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `description` Streamserver host description
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `dir_out` Directory path of resulting track
* `profiles` `{profileName}` `streamserver` `{streamserverName}` `url_out` URL of resulting track
* `profiles` `{profileName}` `app` Application to execute
* `profiles` `{profileName}` `rel_duration_size` Relation between duration and size of track
* `profiles` `{profileName}` `rel_duration_trans` Relation between duration and trans of track
* `profiles` `{profileName}` `prescript` Pre-script to execute
