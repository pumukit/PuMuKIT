<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\FormHandler;

use App\ContentManagement\Series\Application\Update\UpdateSeriesRequest;
use Symfony\Component\HttpFoundation\Request;

final class UpdateSeriesFormHandler
{
    public function handleRequest(Request $request, string $id): UpdateSeriesRequest
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
        $sorting = $request->request->get('sorting');
        $publicDate = $request->request->get('publicDate');

        $title = empty($title) ? null : $title;
        $subtitle = empty($subtitle) ? null : $subtitle;
        $description = empty($description) ? null : $description;
        $header = empty($header) ? null : $header;
        $footer = empty($footer) ? null : $footer;
        $keywords = empty($keywords) ? null : $keywords;

        return new UpdateSeriesRequest(
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
            sorting: $sorting ? (int) $sorting : null,
            seriesTypeId: null,
            seriesStyleId: null,
            properties: null
        );
    }
}
