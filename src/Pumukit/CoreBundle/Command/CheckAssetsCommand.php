<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class CheckAssetsCommand extends Command
{
    public const REPLACEMENTS = [
        'bundles/pumukitnewadmin/js/jquery-1.10.2.min.js' => 'bundles/pumukitcore/vendor/jquery/jquery.min.js',
        'bundles/pumukitnewadmin/js/bootstrap.min.js' => 'bundles/pumukitcore/vendor/bootstrap5/js/bootstrap.bundle.min.js',
        'bundles/pumukitnewadmin/css/bootstrap.min.css' => 'bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css',
        'bundles/pumukitwebtv/js/jquery.min.js' => 'bundles/pumukitcore/vendor/jquery/jquery.min.js',
        'bundles/pumukitcore/vendor/jquery-migrate/jquery-migrate.min.js' => 'nothing: replace the jQuery APIs removed in jQuery 3 (see UPGRADE-6.0.md)',
        'bundles/pumukitwebtv/js/bootstrap.min.js' => 'bundles/pumukitcore/vendor/bootstrap5/js/bootstrap.bundle.min.js',
        'bundles/pumukitcore/vendor/bootstrap/js/bootstrap.min.js' => 'bundles/pumukitcore/vendor/bootstrap5/js/bootstrap.bundle.min.js',
        'bundles/pumukitcore/vendor/bootstrap/css/bootstrap.min.css' => 'bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css',
        'bundles/pumukitwebtv/css/bootstrap.min.css' => 'bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css and bundles/pumukitwebtv/css/webtv.css',
        'bundles/pumukitnewadmin/js/material.min.js' => 'nothing: Bootstrap Material Design is removed',
        'bundles/pumukitnewadmin/js/ripples.min.js' => 'nothing: Bootstrap Material Design is removed',
        'bundles/pumukitnewadmin/css/ripples.min.css' => 'nothing: Bootstrap Material Design is removed',
        'bundles/pumukitnewadmin/css/material-wfont.min.css' => 'bundles/pumukitnewadmin/css/backoffice.css (the mdi-* icons are Font Awesome 7 icons, see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/js/snackbar.min.js' => 'window.success() and window.no_success() (Bootstrap 5 toasts of the backoffice entrypoint)',
        'bundles/pumukitnewadmin/css/snackbar.min.css' => 'nothing: the backoffice uses Bootstrap 5 toasts',
        'bundles/pumukitwebtv/js/moment-with-locales.min.js' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitstatsui/vendor/moment/moment.min.js' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitlive/css/chat.css' => 'bundles/pumukitwebtv/css/live/chat.css',
        'bundles/pumukitnewadmin/js/jquery-ui-1.11.3.custom.min.js' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.min.css' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.structure.min.css' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom.theme.min.css' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.js' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        'bundles/pumukitcore/vendor/jquery-ui/jquery-ui.min.css' => 'nothing: jQuery UI is removed, the backoffice autocompletes use the SuggestInput module of the bundle (see UPGRADE-6.0.md)',
        '@PumukitNewAdmin/jquery-ui-autocomplete.css.twig' => 'nothing: the autocomplete styles are in @PumukitNewAdmin/pumukit.css.twig',
        'bundles/pumukitwebtv/js/bootstrap-datetimepicker.min.js' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitwebtv/css/bootstrap-datetimepicker.min.css' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitwebtv/css/bootstrap-datetimepicker.css' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        '@PumukitCore/Upload/uppy_tus.js.twig' => '@PumukitCore/Upload/uppy_tus.html.twig (include it outside the <script> tag)',
        '@PumukitCore/Upload/uppy_dispatch.js.twig' => '@PumukitCore/Upload/uppy_tus.html.twig (include it outside the <script> tag)',
        '@PumukitCore/Upload/uppy_xhr.js.twig' => 'uploader_attributes({transport: \'xhr\', ...}) on the form (see @PumukitNewAdmin/Media/create.html.twig)',
        '@PumukitCore/Upload/common.js.twig' => 'the core--uploader controller (uploader_attributes() Twig function)',
        '@PumukitCore/Upload/uppy_languages.html.twig' => 'nothing: the core--uploader controller loads the Uppy translations',
        'bundles/pumukitcore/js/uppy.min.js' => 'nothing: the core--uploader controller loads Uppy 6 from the importmap',
        'bundles/pumukitcore/js/uppy-locales/en_US.min.js' => 'nothing: the core--uploader controller loads the Uppy translations',
        'bundles/pumukitcore/js/uppy-locales/es_ES.min.js' => 'nothing: the core--uploader controller loads the Uppy translations',
        'bundles/pumukitcore/js/uppy-locales/gl_ES.min.js' => 'nothing: the core--uploader controller loads the Uppy translations',
        'bundles/pumukitcore/css/uppy.min.css' => 'nothing: the core--uploader controller loads the Uppy styles',
        'bundles/pumukitcore/css/uppy_custom.css' => 'nothing: the file input is a native input now',
        'bundles/pumukitwebtv/js/announces_ajax.js' => 'the webtv--announces controller',
        '@PumukitBaseLivePlayer/Advance/Sessions/common.js.twig' => '@PumukitBaseLivePlayer/Advance/sessions.html.twig (include it outside the <script> tag)',
        '@PumukitBaseLivePlayer/Advance/Sessions/withoutSessions.js.twig' => '@PumukitBaseLivePlayer/Advance/sessions.html.twig (include it outside the <script> tag)',
        '@PumukitBaseLivePlayer/Advance/Sessions/futureSessions.js.twig' => '@PumukitBaseLivePlayer/Advance/sessions.html.twig (include it outside the <script> tag)',
        '@PumukitBaseLivePlayer/Advance/Sessions/nowSessions.js.twig' => '@PumukitBaseLivePlayer/Advance/sessions.html.twig (include it outside the <script> tag)',
        'bundles/pumukitwebtv/js/template_video.js' => 'data-controller="webtv--dynamic-pic" on the image',
        'bundles/pumukitwebtv/js/jquery.countdown.js' => 'the live-player--sessions controller',
        'bundles/pumukitwebtv/js/modernizr-custom.js' => 'native feature detection',
        '@PumukitStatsUI/Stats/js.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/Search/index.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/Search/search.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/Search/views.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/MostViewed/index.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/MostViewed/list.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/Views/index.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        '@PumukitStatsUI/Views/list.html.twig' => 'the statistics panel (see @PumukitStatsUI/Stats/index.html.twig)',
        'bundles/pumukitstatsui/vendor/d3/d3.min.js' => 'Chart.js in the statistics panel controllers',
        'bundles/pumukitstatsui/vendor/nvd3/nv.d3.min.js' => 'Chart.js in the statistics panel controllers',
        'bundles/pumukitstatsui/vendor/nvd3/nv.d3.min.css' => 'Chart.js in the statistics panel controllers',
        '@PumukitNewAdmin/Playlist/listthjs.html.twig' => 'nothing: the sort links of @PumukitNewAdmin/Playlist/listth.html.twig use admin--actions#sort',
        '@PumukitNewAdmin/Pic/upload.html.twig' => 'nothing: the picture uploads answer JSON to the admin--upload-form controller',
        '@PumukitNewAdmin/Pic/upload_event.html.twig' => 'nothing: the picture uploads answer JSON to the admin--upload-form controller',
        '@PumukitNewAdmin/MultimediaObject/acordeon.html.twig' => 'nothing: no page used it',
        '@PumukitNewAdmin/MultimediaObject/updatesocial.html.twig' => 'nothing: no page used it',
        'bundles/pumukitnewadmin/vendor/chartjs/chartjs-plugin-zoom.min.js' => 'nothing: no page used it',
        'bundles/pumukitnewadmin/js/jquery.nouislider.min.js' => 'nothing: no page used it',
        'bundles/pumukitcore/vendor/moment/moment-with-locales.min.js' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitcore/vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitcore/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css' => 'nothing: dates use the native input type="date" (see @PumukitWebTV/Search/filtersmultimediaobjects.html.twig)',
        'bundles/pumukitstatsui/vendor/daterangepicker/daterangepicker.min.js' => 'nothing: the statistics panel uses a range select and two native date inputs',
        'bundles/pumukitstatsui/vendor/daterangepicker/daterangepicker.min.css' => 'nothing: the statistics panel uses a range select and two native date inputs',
        'bundles/pumukitnewadmin/css/materialize-tags.min.css' => 'bundles/pumukitnewadmin/css/keywords.css',
        'bundles/pumukitnewadmin/js/materialize-tags.min.js' => 'nothing: the keywords value of the admin--meta-form controller builds the keyword inputs',
        'bundles/pumukitnewadmin/js/typeahead.bundle.min.js' => 'nothing: the keywords value of the admin--meta-form controller suggests the keywords',
        'bundles/pumukitnewadmin/css/jquery.dropdown.css' => 'nothing: no page used it',
        '@PumukitNewAdmin/Material/upload.html.twig' => 'nothing: the material uploads answer JSON to the admin--upload-form controller',
        '@PumukitNewAdmin/MultimediaObject/updatebroadcast.js.twig' => 'nothing: the admin--broadcast controller of @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig handles the form',
        '@PumukitNewAdmin/pumukit.js.twig' => 'the @pumukit/new-admin/backoffice importmap entrypoint (see @PumukitNewAdmin/layout.html.twig)',
        '@PumukitNewAdmin/UNESCO/listtagsajax.html.twig' => '@PumukitNewAdmin/MultimediaObject/listtagsajax.html.twig (the admin--tag-picker controller of the tag tab handles the clicks)',
        'bundles/pumukitnewadmin/js/hinclude.js' => 'the admin--include controller (see @PumukitNewAdmin/MultimediaObject/index.html.twig)',
        'bundles/pumukitnewadmin/js/select2/select2.min.js' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        'bundles/pumukitnewadmin/js/select2/i18n/en.js' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        'bundles/pumukitnewadmin/js/select2/i18n/es.js' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        'bundles/pumukitnewadmin/js/select2/i18n/gl.js' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        'bundles/pumukitnewadmin/css/select2.min.css' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        '@PumukitNewAdmin/LiveEvent/select2js.html.twig' => 'the jQuery UI autocomplete of the admin--live-event controller (see @PumukitNewAdmin/LiveEvent/updateevent.html.twig)',
        'bundles/pumukitnewadmin/vendor/chartjs/chart.umd.min.js' => 'nothing: the admin--dashboard controller loads Chart.js and its date adapter from the importmap',
        'bundles/pumukitnewadmin/vendor/chartjs/chartjs-adapter-date-fns.bundle.min.js' => 'nothing: the admin--dashboard controller loads Chart.js and its date adapter from the importmap',
        'bundles/pumukitwebtv/css/bootstrap.css' => 'bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css and bundles/pumukitwebtv/css/webtv.css',
        'bundles/pumukitwebtv/css/font-awesome.css' => 'bundles/pumukitcore/vendor/fontawesome/css/all.min.css (Font Awesome 7: icon classes are fa-solid, fa-regular and fa-brands, see UPGRADE-6.0.md)',
        'bundles/pumukitwebtv/css/font-awesome.min.css' => 'bundles/pumukitcore/vendor/fontawesome/css/all.min.css (Font Awesome 7: icon classes are fa-solid, fa-regular and fa-brands, see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/lib/fontawesome/css/all.css' => 'bundles/pumukitcore/vendor/fontawesome/css/all.min.css (Font Awesome 7: icon classes are fa-solid, fa-regular and fa-brands, see UPGRADE-6.0.md)',
        'bundles/pumukitnewadmin/lib/fontawesome/css/all.min.css' => 'bundles/pumukitcore/vendor/fontawesome/css/all.min.css (Font Awesome 7: icon classes are fa-solid, fa-regular and fa-brands, see UPGRADE-6.0.md)',
    ];

    public const REMOVED_JAVASCRIPT_HOOKS = [
        'update_list' => 'a data-refresh-url attribute on #list_mms (see @PumukitNewAdmin/UNESCO/index.html.twig)',
        'submit_delete_mms_unesco' => 'admin--unesco#remove (see @PumukitNewAdmin/UNESCO/updatemenu.html.twig)',
        'submit_clone_mms_unesco' => 'admin--unesco#clone (see @PumukitNewAdmin/UNESCO/updatemenu.html.twig)',
    ];

    public const UNSUPPORTED_TWIG_FUNCTIONS = [
        'render_hinclude' => '<div data-controller="admin--include" data-admin--include-src-value="{{ url(...) }}"></div> (see @PumukitNewAdmin/MultimediaObject/index.html.twig)',
    ];

    public const MATERIAL_ICONS = [
        'action-dns' => 'fa-solid fa-server',
        'action-done' => 'fa-solid fa-check',
        'action-exit-to-app' => 'fa-solid fa-arrow-up-right-from-square',
        'action-info' => 'fa-solid fa-circle-info',
        'action-list' => 'fa-solid fa-list',
        'action-lock' => 'fa-solid fa-lock',
        'action-report-problem' => 'fa-solid fa-triangle-exclamation',
        'action-spellcheck' => 'fa-solid fa-spell-check',
        'action-translate' => 'fa-solid fa-language',
        'action-visibility' => 'fa-solid fa-eye',
        'alert-warning' => 'fa-solid fa-triangle-exclamation',
        'av-equalizer' => 'fa-solid fa-chart-simple',
        'av-loop' => 'fa-solid fa-repeat',
        'av-new-releases' => 'fa-solid fa-circle-exclamation',
        'av-pause-circle-fill' => 'fa-solid fa-circle-pause',
        'av-play-arrow' => 'fa-solid fa-play',
        'av-play-circle-fill' => 'fa-solid fa-circle-play',
        'av-playlist-add' => 'fa-solid fa-list-ul',
        'content-add' => 'fa-solid fa-plus',
        'content-block' => 'fa-solid fa-ban',
        'content-clear' => 'fa-solid fa-xmark',
        'content-content-copy' => 'fa-solid fa-copy',
        'content-content-cut' => 'fa-solid fa-scissors',
        'content-forward' => 'fa-solid fa-share',
        'content-save' => 'fa-solid fa-floppy-disk',
        'device-signal-wifi-0-bar' => 'fa-solid fa-wifi',
        'device-signal-wifi-4-bar' => 'fa-solid fa-wifi',
        'device-signal-wifi-off' => 'fa-solid fa-ban',
        'device-wifi-lock' => 'fa-solid fa-lock',
        'editor-insert-photo' => 'fa-solid fa-image',
        'editor-mode-edit' => 'fa-solid fa-pencil',
        'editor-vertical-align-bottom' => 'fa-solid fa-arrows-down-to-line',
        'editor-vertical-align-top' => 'fa-solid fa-arrows-up-to-line',
        'file-file-download' => 'fa-solid fa-download',
        'file-folder' => 'fa-solid fa-folder',
        'hardware-keyboard-arrow-down' => 'fa-solid fa-angle-down',
        'image-add-to-photos' => 'fa-solid fa-square-plus',
        'image-camera' => 'fa-solid fa-camera',
        'image-camera-alt' => 'fa-solid fa-camera',
        'image-camera-roll' => 'fa-solid fa-film',
        'image-timer-auto' => 'fa-solid fa-circle-user',
        'navigation-chevron-right' => 'fa-solid fa-angle-right',
        'navigation-expand-more' => 'fa-solid fa-angle-down',
        'navigation-menu' => 'fa-solid fa-bars',
        'navigation-refresh' => 'fa-solid fa-arrows-rotate',
        'social-group' => 'fa-solid fa-users',
    ];

    public const REMOVED_JQUERY_APIS = [
        'trim' => 'String.prototype.trim()',
        'isArray' => 'Array.isArray()',
        'isFunction' => 'typeof value === \'function\'',
        'isNumeric' => 'Number.isFinite()',
        'isWindow' => 'value != null && value === value.window',
        'nodeName' => 'element.nodeName.toLowerCase()',
        'now' => 'Date.now()',
        'parseJSON' => 'JSON.parse()',
        'type' => 'typeof, Array.isArray() or instanceof',
        'unique' => '$.uniqueSort()',
        'camelCase' => 'a local helper',
        'cssProps' => 'nothing: vendor prefixes are no longer needed',
    ];

    public const REMOVED_JQUERY_UI_WIDGETS = [
        'autocomplete' => 'the SuggestInput module of the backoffice (see UPGRADE-6.0.md)',
    ];

    public const REMOVED_JAVASCRIPT_GLOBALS = [
        'uppyTUS' => 'the core--uploader controller (uploader_attributes() Twig function)',
        'Uppy' => 'the core--uploader controller (uploader_attributes() Twig function)',
        'convertSizeToBytes' => 'the core--uploader controller (sizes come from uploader_attributes())',
        'sendInboxXHRImportRequest' => 'the import option of uploader_attributes()',
        'show_file_added' => 'the core--uploader:added event',
        'file_data_clear' => 'the core--uploader#remove action',
        'submit_delete_role' => 'admin--actions#remove (see @PumukitNewAdmin/Role/list.html.twig)',
        'submit_delete_user' => 'admin--actions#remove (see @PumukitNewAdmin/User/list.html.twig)',
        'submit_delete_live' => 'admin--actions#remove (see @PumukitNewAdmin/Live/list.html.twig)',
        'submit_delete_seriestype' => 'admin--actions#remove (see @PumukitNewAdmin/Seriestype/list.html.twig)',
        'submit_delete_event' => 'admin--actions#remove (see @PumukitNewAdmin/LegacyEvent/array.html.twig)',
        'delete_eventpic' => 'admin--actions#remove (see @PumukitNewAdmin/LegacyEvent/updatemenu.html.twig)',
        'submit_delete_permissionprofile' => 'admin--actions#remove (see @PumukitNewAdmin/PermissionProfile/updatemenu.html.twig)',
        'submit_delete_person' => 'admin--actions#remove (see @PumukitNewAdmin/Person/list.html.twig)',
        'submit_delete_group' => 'the admin--group-info controller',
        'submit_delete_link' => 'admin--actions#remove (see @PumukitNewAdmin/Link/list.html.twig)',
        'filter_click_letter' => 'admin--filter-form#letter',
        'processPermissionProfilesOk' => 'the admin--permission-profiles controller',
        'checkScope' => 'the admin--permission-profiles controller',
        'add_group_user' => 'the admin--group-picker controller',
        'delete_group_user' => 'the admin--group-picker controller',
        'delete_user_from_group' => 'the admin--group-info controller',
        'delete_multimediaobject_from_group' => 'the admin--group-info controller',
        'delete_embeddedbroadcast_from_group' => 'the admin--group-info controller',
        'can_be_deleted' => 'the admin--group-info controller',
        'toggle_section_tag' => 'the admin--tag-tree controller',
        'tag_delete' => 'the admin--tag-tree controller',
        'fileServerTree' => 'the admin--file-tree controller',
        'fileServerSelect' => 'the admin--file-tree controller',
        'click_fila_url' => 'admin--actions#preview',
        'encoder_reload_timeout' => 'the encoder--stats controller',
        'url_latestuploads_pager' => 'the webtv--announces controller',
        'insertCookieUser' => 'the webtv--cookies controller',
        'resizeFrame' => 'data-controller="webtv--player-frame" on the player iframe',
        'submitForm' => 'the webtv--search controller',
        'toggleDateSelect' => 'the webtv--search controller',
        'postChatMessage' => 'the webtv--chat controller',
        'updateChat' => 'the webtv--chat controller',
        'Modernizr' => 'native feature detection',
        'dynamic_image' => 'data-controller="webtv--dynamic-pic" on the image',
        'submit_generate_magic_series' => 'admin--actions#magicUrl (see @PumukitNewAdmin/Series/links.html.twig)',
        'submit_clone_series' => 'admin--actions#load (see @PumukitNewAdmin/Series/updatemenu.html.twig)',
        'submit_delete_series' => 'admin--actions#remove (see @PumukitNewAdmin/Series/updatemenu.html.twig)',
        'submit_delete_playlist' => 'admin--actions#remove (see @PumukitNewAdmin/Playlist/updatemenu.html.twig)',
        'click_checkbox_changepub_all' => 'the admin--changepub controller',
        'check_checkbox_all' => 'the admin--changepub controller',
        'check_checkbox_channel_all' => 'the admin--changepub controller',
        'click_checkbox_channel_changepub_all' => 'the admin--changepub controller',
        'submit_series_change_pub' => 'the admin--changepub controller',
        'init_series_materialtags' => 'the admin--meta-form controller',
        'up_seriespic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'down_seriespic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'up_playlistpic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'down_playlistpic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'up_mmspic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'down_mmspic' => 'admin--actions#load (see @PumukitNewAdmin/Pic/list.html.twig)',
        'delete_seriespic' => 'admin--actions#remove (see @PumukitNewAdmin/Pic/list.html.twig)',
        'delete_playlistpic' => 'admin--actions#remove (see @PumukitNewAdmin/Pic/list.html.twig)',
        'delete_mmspic' => 'admin--actions#remove (see @PumukitNewAdmin/Pic/list.html.twig)',
        'delete_eventspic' => 'admin--actions#remove (see @PumukitNewAdmin/Pic/list.html.twig)',
        'update_mmspic' => 'the admin--pic-list controller',
        'update_seriespic' => 'the admin--pic-list controller',
        'update_playlistpic' => 'the admin--pic-list controller',
        'change_pics_page' => 'admin--actions#load (see @PumukitNewAdmin/Pic/picstoaddlist.html.twig)',
        'pmk_translate' => 'nothing: the metadata translation button was never implemented and is removed',
        'submit_delete_mms' => 'admin--actions#remove (see @PumukitNewAdmin/MultimediaObject/updatemenu.html.twig)',
        'submit_clone_mms' => 'admin--actions#load (see @PumukitNewAdmin/MultimediaObject/updatemenu.html.twig)',
        'update_mms_status_wrapper' => 'the admin--job-status controller (see @PumukitNewAdmin/MultimediaObject/index.html.twig)',
        'check_for_new_pics' => 'the admin--pic-sync controller (see @PumukitNewAdmin/MultimediaObject/status.html.twig)',
        'upload_mmspic' => 'the admin--pic-sync controller (see @PumukitNewAdmin/MultimediaObject/status.html.twig)',
        'change_select' => 'the admin--batch controller (see @PumukitNewAdmin/MultimediaObject/index.html.twig)',
        'get_batch_delete_route' => 'the urls value of the admin--batch controller',
        'sort_optiont_change' => 'the data-url of the sort options of the admin--batch controller',
        'update_preview_mms' => 'nothing: it was never called',
        'procesaReset' => 'nothing: the elements it hid no longer exist',
        'select_tree_tag' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'toggle_tree_tag' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'add_tree_tag' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'del_tree_tag' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'add_tree_tag_unesco' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'del_tree_tag_unesco' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'search_tags_wrapper' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'search_tags' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'reset_tags' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'inc_num_mm' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'create_element_in_select' => 'the admin--tag-picker controller (see @PumukitNewAdmin/MultimediaObject/updatetag.html.twig)',
        'submit_generate_magic_mms' => 'admin--actions#magicUrl (see @PumukitNewAdmin/MultimediaObject/links.html.twig)',
        'sync_tags_mms' => 'admin--actions#request with a confirm param (see @PumukitNewAdmin/UNESCO/updatetag.html.twig)',
        'select_group' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'add_group' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'delete_group' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'create_element_group_in_add_groups' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'create_element_group_in_delete_groups' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'delete_element_group_from_select' => 'the admin--group-lists controller (see @PumukitNewAdmin/MultimediaObject/listgrouprelation.html.twig)',
        'submit_delete_person_relation' => 'the admin--mm-groups controller (see @PumukitNewAdmin/MultimediaObject/updategroup.html.twig)',
        'is_last_user_relation_to_admin_mm' => 'the admin--mm-groups controller (see @PumukitNewAdmin/MultimediaObject/updategroup.html.twig)',
        'pumukitnewadmin_mm_updategroup_submit' => 'the admin--mm-groups controller (see @PumukitNewAdmin/MultimediaObject/updategroup.html.twig)',
        'pumukitnewadmin_mm_updategroup_reset' => 'the admin--mm-groups controller (see @PumukitNewAdmin/MultimediaObject/updategroup.html.twig)',
        'check_pass' => 'the admin--broadcast controller (see @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig)',
        'generate_random_password' => 'the admin--broadcast controller (see @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig)',
        'can_enable_broadcast_groups_save_button' => 'the admin--broadcast controller (see @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig)',
        'can_enable_broadcast_password_save_button' => 'the admin--broadcast controller (see @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig)',
        'get_submit_button_id' => 'the admin--broadcast controller (see @PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig)',
        'click_checkbox_change_pub_mms' => 'the checkOne action of the admin--broadcast controller (see @PumukitNewAdmin/Series/updatebroadcast.html.twig)',
        'click_changepub_selection' => 'the selectAll action of the admin--broadcast controller (see @PumukitNewAdmin/Series/updatebroadcast.html.twig)',
        'submit_delete_material' => 'admin--actions#remove (see @PumukitNewAdmin/Material/list.html.twig)',
        'submit_delete_track' => 'admin--actions#remove with a refresh param (see @PumukitNewAdmin/Media/Type/Resources/_delete.html.twig)',
        'update_list' => 'the refresh param of admin--actions#remove',
        'extract_pic' => 'the extractPic action of the admin--track-list controller (see @PumukitNewAdmin/Media/Type/Resources/_thumbnail_generator.html.twig)',
        'toggleHide' => 'admin--actions#load (see @PumukitNewAdmin/Media/Type/Resources/_visibility.html.twig)',
        'getAllDnDChecked' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'dragEndCheckbox' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'dragItem' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'dragEnd' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'dropTagItem' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'updateMultimediaObjectByTag' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'dropItem' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'updateMultimediaObject' => 'the admin--unesco controller (see @PumukitNewAdmin/UNESCO/_controller.html.twig)',
        'procesaOk' => 'admin--meta-form#hideRemember (see @PumukitNewAdmin/UNESCO/updatepub.html.twig)',
        'dblclick_fila_url' => 'admin--actions#navigate',
        'click_fila_edit_urls' => 'admin--actions#edit (see @PumukitNewAdmin/LiveEvent/list.html.twig)',
        'init_mmobj_materialtags' => 'the keywords value of the admin--meta-form controller (see @PumukitNewAdmin/UNESCO/updatemeta.html.twig)',
        'dblclick_preview_mm' => 'admin--actions#openModal (see @PumukitNewAdmin/PlaylistMultimediaObject/list.html.twig)',
        'playlist_mms_options' => 'the batch action of the admin--playlist-mms controller (see @PumukitNewAdmin/PlaylistMultimediaObject/index.html.twig)',
        'mm_delete_selected' => 'the batch action of the admin--playlist-mms controller (see @PumukitNewAdmin/PlaylistMultimediaObject/index.html.twig)',
        'delete_mm' => 'the remove action of the admin--playlist-mms controller (see @PumukitNewAdmin/PlaylistMultimediaObject/updatemenu.html.twig)',
        'Bloodhound' => 'the keywords value of the admin--meta-form controller (see @PumukitNewAdmin/MultimediaObject/updatemeta.html.twig)',
        'init_tabs' => 'the admin--tabs controller (see @PumukitNewAdmin/Series/update.html.twig)',
        'click_checkbox_all' => 'admin--actions#checkAll (see @PumukitNewAdmin/Place/parent_listth.html.twig)',
        'checkRecordDateAndPublicDate' => 'the dates value of the admin--meta-form controller (see @PumukitNewAdmin/MultimediaObject/updatemeta.html.twig)',
    ];

    private const REFERENCE_PATTERN = '#(?<![\w./-])/?(bundles/([a-z0-9_]+)/[^\'"\s?\#{}()]*)#';
    private const TEMPLATE_PATTERN = '#[\'"](@Pumukit[A-Za-z]+/[^\'"]+\.twig)[\'"]#';
    private const PORTAL_LAYOUT = 'PumukitWebTVBundle/Layout/template.html.twig';
    private const UTILITIES_STYLESHEET = 'bundles/pumukitcore/css/pumukit-utilities.css';
    private const FONT_AWESOME_STYLESHEET = 'vendor/fontawesome/css/all.min.css';
    private const MATERIAL_ICON_PATTERN = '/(?<![\w.-])mdi-([a-z][a-z0-9-]*)/';
    private const ICON_PATTERN = '/(?<![\w-])(?:fa|fas|far|fab|fa-solid|fa-regular|fa-brands)\s+fa-([a-z0-9-]+)/';
    private const OPTIONAL_TEMPLATE_PATTERN = '#\{%-?\s*(extends|include|embed)\s*\[|ignore missing#';

    private KernelInterface $kernel;
    private Environment $twig;
    private string $projectDir;

    public function __construct(KernelInterface $kernel, Environment $twig, #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:check:assets')
            ->setDescription('List references in Twig templates to bundle assets and PuMuKIT templates that do not exist')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Directory with the templates to check (default: the project templates directory)', null)
            ->setHelp(
                <<<'EOT'
The <info>pumukit:check:assets</info> command looks for references to bundle public assets
(<comment>bundles/<bundle>/...</comment>) and to PuMuKIT templates (<comment>@PumukitXxx/...twig</comment>)
in the Twig templates of a directory and reports the ones that no installed bundle provides.
When the asset or template was moved, it shows the one that replaces it. It also reports an
overridden portal layout (<comment>PumukitWebTVBundle/Layout/template.html.twig</comment>) that does not
load <comment>importmap('pumukit')</comment> or <comment>bundles/pumukitcore/css/pumukit-utilities.css</comment>, the jQuery utilities that jQuery 4 removed (<comment>$.trim</comment>,
<comment>$.isArray</comment>...), the Font Awesome icons that Font Awesome 7 does not provide and the removed Material Design
icons (<comment>mdi-*</comment>).

Run it on the overridden templates of a project before upgrading PuMuKIT. It exits with an
error code when a missing asset is found.

    Example:
        php bin/console pumukit:check:assets
        php bin/console pumukit:check:assets --path=templates/bundles/PumukitWebTVBundle
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path') ?? $this->projectDir.'/templates';
        if (!is_dir($path)) {
            $output->writeln('<error>Path does not exist: '.$path.'</error>');

            return Command::FAILURE;
        }

        $publicDirs = $this->bundlePublicDirs();
        $missing = 0;

        $files = [];
        foreach ((new Finder())->files()->in($path)->name('*.twig')->sortByName() as $file) {
            $files[$file->getRelativePathname()] = file($file->getPathname());
        }
        $removedGlobals = $this->removedGlobalsNotDefinedIn($files);
        $icons = $this->fontAwesomeIcons($publicDirs);

        foreach ($files as $relativePathname => $lines) {
            foreach ($lines as $index => $line) {
                $location = $relativePathname.':'.($index + 1);

                foreach ($removedGlobals as $global) {
                    if (preg_match('/(?:(?<![\w$.])|(?<=window\.))'.$global.'\b(?![-{])/', $line)) {
                        ++$missing;
                        $output->writeln('Removed JavaScript global: <info>'.$global.'</info> in '.$location);
                        $output->writeln('    use <comment>'.self::removedGlobals()[$global].'</comment>');
                    }
                }

                preg_match_all(self::MATERIAL_ICON_PATTERN, $line, $matches);
                foreach (array_unique($matches[1]) as $materialIcon) {
                    ++$missing;
                    $output->writeln('Removed Material Design icon: <info>mdi-'.$materialIcon.'</info> in '.$location.', the backoffice no longer loads its font');
                    $output->writeln('    use <comment>'.(self::MATERIAL_ICONS[$materialIcon] ?? 'a Font Awesome 7 icon (fa-solid fa-...)').'</comment>');
                }

                if ([] !== $icons) {
                    preg_match_all(self::ICON_PATTERN, $line, $matches);
                    foreach (array_unique($matches[1]) as $icon) {
                        if (!isset($icons[$icon])) {
                            ++$missing;
                            $output->writeln('Unknown Font Awesome icon: <info>fa-'.$icon.'</info> in '.$location.', Font Awesome 7 does not provide it');
                            $output->writeln('    use <comment>the Font Awesome 7 name of the icon (see UPGRADE-6.0.md)</comment>');
                        }
                    }
                }

                foreach (self::REMOVED_JQUERY_APIS as $api => $replacement) {
                    if (preg_match('/(?<![\w$.])(?:\$|jQuery)\.'.$api.'\b/', $line)) {
                        ++$missing;
                        $output->writeln('Removed jQuery API: <info>$.'.$api.'</info> in '.$location.', jQuery 4 no longer provides it');
                        $output->writeln('    use <comment>'.$replacement.'</comment>');
                    }
                }

                foreach (self::REMOVED_JQUERY_UI_WIDGETS as $widget => $replacement) {
                    if (preg_match('/\.'.$widget.'\(/', $line)) {
                        ++$missing;
                        $output->writeln('Removed jQuery UI widget: <info>.'.$widget.'()</info> in '.$location.', jQuery UI is no longer loaded');
                        $output->writeln('    use <comment>'.$replacement.'</comment>');
                    }
                }

                foreach (self::REMOVED_JAVASCRIPT_HOOKS as $hook => $replacement) {
                    if (preg_match('/(?<![\w$.])(?:window\.)?'.$hook.'\s*=(?!=)/', $line)) {
                        ++$missing;
                        $output->writeln('Removed JavaScript hook: <info>'.$hook.'</info> in '.$location.', the backoffice no longer calls it');
                        $output->writeln('    use <comment>'.$replacement.'</comment>');
                    }
                }

                foreach (self::UNSUPPORTED_TWIG_FUNCTIONS as $function => $replacement) {
                    if (preg_match('/(?<![\w.])'.$function.'\s*\(/', $line)) {
                        ++$missing;
                        $output->writeln('Unsupported Twig function: <info>'.$function.'</info> in '.$location.', the backoffice no longer loads its script');
                        $output->writeln('    use <comment>'.$replacement.'</comment>');
                    }
                }

                preg_match_all(self::REFERENCE_PATTERN, $line, $matches, PREG_SET_ORDER);
                foreach ($matches as [, $reference, $bundle]) {
                    if (!$this->exists($publicDirs, $bundle, $reference)) {
                        ++$missing;
                        $this->report($output, 'Missing asset', $reference, $location);
                    }
                }

                if (preg_match(self::OPTIONAL_TEMPLATE_PATTERN, $line)) {
                    continue;
                }

                preg_match_all(self::TEMPLATE_PATTERN, $line, $matches, PREG_SET_ORDER);
                foreach ($matches as [, $template]) {
                    if (!$this->templateExists($path, $template)) {
                        ++$missing;
                        $this->report($output, 'Missing template', $template, $location);
                    }
                }
            }

            if (str_ends_with($relativePathname, self::PORTAL_LAYOUT) && !str_contains(implode('', $lines), 'importmap(')) {
                ++$missing;
                $output->writeln('Missing <info>{{ importmap(\'pumukit\') }}</info> in the javascripts block of '.$relativePathname);
            }

            if (str_ends_with($relativePathname, self::PORTAL_LAYOUT) && !str_contains(implode('', $lines), self::UTILITIES_STYLESHEET)) {
                ++$missing;
                $output->writeln('Missing <info>'.self::UTILITIES_STYLESHEET.'</info> stylesheet in '.$relativePathname.', the core templates use its classes instead of inline styles');
            }
        }

        $output->writeln('<comment>***** Problems found: '.$missing.' *****</comment>');

        return $missing > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private static function removedGlobals(): array
    {
        return self::REMOVED_JAVASCRIPT_GLOBALS;
    }

    private function removedGlobalsNotDefinedIn(array $files): array
    {
        $content = implode('', array_merge(...array_values($files)));

        return array_values(array_filter(
            array_keys(self::removedGlobals()),
            static fn (string $global): bool => !preg_match('/(function\s+'.$global.'\b|\b(var|let|const)\s+'.$global.'\b|(?:(?<![\w$.])|(?<=window\.))'.$global.'\s*=(?!=))/', $content)
        ));
    }

    private function templateExists(string $path, string $template): bool
    {
        if ($this->twig->getLoader()->exists($template)) {
            return true;
        }

        [$namespace, $relativePath] = explode('/', substr($template, 1), 2);

        return file_exists($path.'/bundles/'.$namespace.'Bundle/'.$relativePath);
    }

    private function report(OutputInterface $output, string $problem, string $reference, string $location): void
    {
        $output->writeln($problem.': <info>'.$reference.'</info> in '.$location);
        if (isset(self::REPLACEMENTS[$reference])) {
            $output->writeln('    use <comment>'.self::REPLACEMENTS[$reference].'</comment>');
        }
    }

    private function fontAwesomeIcons(array $publicDirs): array
    {
        $stylesheet = ($publicDirs['pumukitcore'] ?? '').'/'.self::FONT_AWESOME_STYLESHEET;
        if (!is_file($stylesheet)) {
            return [];
        }

        preg_match_all('/\.fa-([a-z0-9-]+)/', (string) file_get_contents($stylesheet), $matches);

        return array_fill_keys($matches[1], true);
    }

    private function bundlePublicDirs(): array
    {
        $dirs = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $name = preg_replace('/bundle$/', '', strtolower($bundle->getName()));
            foreach ([$bundle->getPath().'/Resources/public', $bundle->getPath().'/public'] as $dir) {
                if (is_dir($dir)) {
                    $dirs[$name] = $dir;

                    break;
                }
            }
        }

        return $dirs;
    }

    private function exists(array $publicDirs, string $bundle, string $reference): bool
    {
        if (!isset($publicDirs[$bundle])) {
            return false;
        }

        $relativePath = substr($reference, strlen('bundles/'.$bundle));

        return file_exists($publicDirs[$bundle].$relativePath);
    }
}
