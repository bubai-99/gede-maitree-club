{{-- Data for resources/js/panjika (the committee's days, club events, the panjika followed). --}}
<script type="application/json" id="panjika-data">@json(app(\App\Support\PanjikaData::class)->forScript($surface ?? 'site'))</script>
