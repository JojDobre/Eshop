<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; line-height: 1.5; color: #18181b; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        h2 { font-size: 12pt; margin: 14pt 0 4pt; }
        h3 { font-size: 10.5pt; margin: 10pt 0 4pt; }
        .meta { color: #71717a; font-size: 8.5pt; margin-bottom: 14pt; }
    </style>
</head>
<body>
    <h1>{{ $document->title }}</h1>
    <div class="meta">{{ __('orders::mail.document_meta', ['shop' => $shopName, 'version' => $document->version, 'date' => $document->publishedAt->format('j. n. Y')]) }}</div>
    {!! $document->html !!}
</body>
</html>
