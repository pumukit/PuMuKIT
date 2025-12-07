<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\FormHandler;

use App\ContentManagement\Playlist\Application\Update\UpdatePlaylistRequest;
use Symfony\Component\HttpFoundation\Request;

final class UpdatePlaylistFormHandler
{
    public function handleRequest(Request $request, string $id): UpdatePlaylistRequest
    {
        $title = $request->request->all('title');
        $subtitle = $request->request->all('subtitle');
        $description = $request->request->all('description');
        $header = $request->request->all('header');
        $footer = $request->request->all('footer');
        $keywords = $request->request->all('keywords');
        $comments = $request->request->get('comments');
        $announce = null !== $request->request->get('announce');
        $hide = null !== $request->request->get('hide');
        $publicDate = $request->request->get('publicDate');

        $title = !empty($title) ? $title : null;
        $subtitle = !empty($subtitle) ? $subtitle : null;
        $description = !empty($description) ? $description : null;
        $header = !empty($header) ? $header : null;
        $footer = !empty($footer) ? $footer : null;
        $keywords = !empty($keywords) ? $keywords : null;

        return new UpdatePlaylistRequest(
            id: $id,
            title: $title,
            subtitle: $subtitle,
            description: $description,
            header: $header,
            footer: $footer,
            comments: $comments,
            keywords: $keywords,
            announce: $announce,
            hide: $hide,
            publicDate: $publicDate ? new \DateTime($publicDate) : null,
            properties: null
        );
    }
}
