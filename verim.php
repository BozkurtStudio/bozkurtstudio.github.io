
https://api.themoviedb.org/3/tv/1459?api_key=9fe4d6dc1fc8313108d7d43861987347&language=tr&append_to_response=seasons

<?php

// TMDb API anahtarınız
$api_key = '9fe4d6dc1fc8313108d7d43861987347';

// Anime ID'si
$anime_id = 22349;

// API isteği için URL
$url = "https://api.themoviedb.org/3/tv/{$anime_id}?api_key={$api_key}";

// API'ye istek yap
$response = file_get_contents($url);

// Yanıtı JSON'a çevir
$data = json_decode($response);

// Bölüm bilgilerini listele
$episodes = array();
foreach ($data->seasons as $season) {
    foreach ($season->episodes as $episode) {
        $episodes[] = array(
            'title' => $episode->name,
            'thumbnail' => "https://image.tmdb.org/t/p/w500{$episode->still_path}",
            'overview' => $episode->overview
        );
    }
}

// Sonuçları JSON olarak kodla
echo json_encode($episodes);
