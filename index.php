<!-- 1 -->
<link title="timeline-styles" rel="stylesheet" 
        href="https://cdn.knightlab.com/libs/timeline3/latest/css/timeline.css">

<!-- 2 -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.knightlab.com/libs/timeline3/latest/js/timeline.js"></script>

<div id='timeline-embed' style="width: 100%; height: 100%"></div>

<!-- 3 -->
<script type="text/javascript">
    // The TL.Timeline constructor takes at least two arguments:
    // the id of the Timeline container (no '#'), and
    // the URL to your JSON data file or Google spreadsheet.
    // the id must refer to an element "above" this code,
    // and the element must have CSS styling to give it width and height
    // optionally, a third argument with configuration options can be passed.
    // See below for more about options.

    var additionalOptions = {
        font: 'calibri-roboto',
        start_at_end: true
    }

    timeline = new TL.Timeline('timeline-embed',
    'https://docs.google.com/spreadsheets/d/1cWqQBZCkX9GpzFtxCWHoqFXCHg-ylTVUWlnrdYMzKUI/pubhtml',
    additionalOptions);
</script>