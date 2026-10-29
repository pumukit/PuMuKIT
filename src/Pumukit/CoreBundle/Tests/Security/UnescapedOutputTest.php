<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Security;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
#[CoversNothing]
class UnescapedOutputTest extends TestCase
{
    private const PATTERNS = [
        'twig' => ['/\|\s*(raw|unescape)\b/', '/autoescape\s+false/'],
        'php' => ["/'is_safe'\\s*=>/"],
        'js' => ["/\\.html\\((?!\\s*\\)|\\s*''\\s*\\))/", '/\b(innerHTML|outerHTML)\s*\+?=(?!=)/', '/insertAdjacentHTML|document\.write/'],
    ];

    private const REVIEWED = [
        'BaseLivePlayerBundle/Resources/assets/controllers/live-player/sessions_controller.js' => [
            '.html(this.posterTarget.innerHTML)',
        ],
        'CoreBundle/Twig/CoreExtension.php' => [
            "new TwigFilter('pumukit_spaceless'",
        ],
        'CoreBundle/Twig/UploaderExtension.php' => [
            "new TwigFunction('uploader_attributes'",
        ],
        'EncoderBundle/Resources/views/Info/infoJob.html.twig' => [
            '{{ command|e|replace({\' \':\'&nbsp;\', \'\n\': \'</li><li class="command">\'})|raw }}',
            "{{ job.getOutput()|e|replace({' ':'&nbsp;', '\\n': '</li><li>'})|raw }}",
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/actions_controller.js' => [
            '.html(clear.message)',
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/batch_controller.js' => [
            '.html(response)',
            '.html(response)',
            '.html(response)',
            ".html(this.message('selectAny'))",
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/group_info_controller.js' => [
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/group_picker_controller.js' => [
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/live_event_controller.js' => [
            '.html(response)',
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/modal_form_controller.js' => [
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/permission_profiles_controller.js' => [
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/places_controller.js' => [
            '.html($(\'<tr class="text-center pmk-font-size-12"><td colspan="8"><img class="center"></td></tr>\').find(\'img\').attr(\'src\', this.spinnerValue).end())',
            '.html(response)',
        ],
        'NewAdminBundle/Resources/assets/controllers/admin/track_list_controller.js' => [
            '.html(this.statusValue.html)',
            '.html(response)',
        ],
        'NewAdminBundle/Resources/views/HeadAndTail/_list.html.twig' => [
            '{{ element.isPublished() ? greenIcon|raw : redIcon|raw }}',
            "{{ element.containsTagWithCod('PUCHWEBTV') ? greenIcon|raw : redIcon|raw }}",
            '{{ element.getDisplayTrack() ? greenIcon|raw : redIcon|raw }}',
        ],
        'NewAdminBundle/Resources/views/Media/info.html.twig' => [
            '{{ command|e|replace({\' \':\'&nbsp;\', \'\n\': \'</li><li class="command">\'})|raw }}',
            "{{ job.getOutput()|e|replace({' ':'&nbsp;', '\\n': '</li><li>'})|raw }}",
        ],
        'NewAdminBundle/Resources/views/Person/listth.html.twig' => [
            '{{ typeIcon|raw }}',
            '{{ typeIcon|raw  }}',
            '{{ typeIcon|raw  }}',
            '{{ typeIcon|raw  }}',
        ],
        'NewAdminBundle/Resources/views/Pic/generate.html.twig' => [
            '{{ track_url(track)|raw }}',
        ],
        'NewAdminBundle/Resources/views/Themes/fields.html.twig' => [
            '})|raw }}',
            '{{ widget|raw }}',
        ],
        'NewAdminBundle/Resources/views/Themes/menu.html.twig' => [
            '{{- \' %s="%s"\'|format(name, value is same as(true) ? name|e : value|e)|raw -}}',
            '{{ item.label|raw }}',
        ],
        'NewAdminBundle/Resources/views/UNESCO/listbody.html.twig' => [
            '{{ text|striptags|unescape }}',
            '{{ text|raw }}',
            '{{ text|raw }}',
        ],
        'NewAdminBundle/Resources/views/UNESCO/listth.html.twig' => [
            '{{ icon|raw }}',
        ],
        'NotificationBundle/Resources/views/Email/notification.html.twig' => [
            '{{ body|raw }}',
        ],
        'WebTVBundle/Resources/assets/controllers/webtv/chat_controller.js' => [
            ".html(trans('Sorry but there was an error'))",
        ],
        'WebTVBundle/Resources/assets/controllers/webtv/edit_button_controller.js' => [
            '.html(response)',
        ],
        'WebTVBundle/Resources/views/Css/template_footer.css.twig' => [
            '{{ primary_color|raw }}',
        ],
        'WebTVBundle/Resources/views/Css/template_horizontal_menu.css.twig' => [
            '{{ primary_color|raw }}',
            '{{ primary_color|raw }}',
        ],
        'WebTVBundle/Resources/views/Css/template_pagination.css.twig' => [
            '{{ primary_color|raw }}',
            '{{ primary_color|raw }}',
        ],
        'WebTVBundle/Resources/views/MultimediaObject/generic.html.twig' => [
            '{% set url_iframe = path(iframe_route, iframe_parameters)|raw %}',
        ],
        'WebTVBundle/Resources/views/MultimediaObject/player.html.twig' => [
            '{% set url_iframe = path(iframe_route, iframe_parameters)|raw %}',
        ],
        'WebTVBundle/Resources/views/default.css.twig' => [
            '{% autoescape false %}',
            '{{ primary_color|raw }}',
            '{{ primary_color|raw }}',
            '{{ primary_color|raw }}',
        ],
    ];

    public function testEveryUnescapedOutputHasBeenReviewed(): void
    {
        $unreviewed = [];
        $reviewed = self::REVIEWED;

        foreach ($this->files() as $path => $type) {
            $lines = file(dirname(__DIR__, 3).'/'.$path, FILE_IGNORE_NEW_LINES);
            foreach ($lines as $number => $line) {
                if (!$this->isUnescapedOutput($type, $line)) {
                    continue;
                }
                $index = $this->reviewedIndex($reviewed[$path] ?? [], $line);
                if (null === $index) {
                    $unreviewed[] = $path.':'.($number + 1).' '.trim($line);

                    continue;
                }
                unset($reviewed[$path][$index]);
            }
        }

        $this->assertSame([], $unreviewed, 'Escape these outputs, or review them and add them to REVIEWED.');
        $this->assertSame([], array_filter($reviewed), 'These REVIEWED entries no longer exist: remove them.');
    }

    private function files(): iterable
    {
        $finder = (new Finder())
            ->files()
            ->in(dirname(__DIR__, 3))
            ->name(['*.twig', '*.php', '*.js'])
            ->notPath(['#/Tests/#', '#/vendor/#', '#^PaellaPlayerBundle/Resources/public/#'])
            ->notName('*.min.js')
            ->sortByName()
        ;

        foreach ($finder as $file) {
            $path = $file->getRelativePathname();
            $type = $file->getExtension();
            if ('js' === $type && !preg_match('#/Resources/(assets|public/js)/#', $path)) {
                continue;
            }

            yield $path => $type;
        }
    }

    private function isUnescapedOutput(string $type, string $line): bool
    {
        foreach (self::PATTERNS[$type] as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    private function reviewedIndex(array $snippets, string $line): ?int
    {
        foreach ($snippets as $index => $snippet) {
            if (str_contains($line, $snippet)) {
                return $index;
            }
        }

        return null;
    }
}
