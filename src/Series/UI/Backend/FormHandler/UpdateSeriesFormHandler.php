<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\FormHandler;

use App\Series\Application\Update\UpdateSeriesRequest;
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

        // Clean empty arrays
        $title = !empty($title) ? $title : null;
        $subtitle = !empty($subtitle) ? $subtitle : null;
        $description = !empty($description) ? $description : null;
        $header = !empty($header) ? $header : null;
        $footer = !empty($footer) ? $footer : null;
        $keywords = !empty($keywords) ? $keywords : null;

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

