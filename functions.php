<?php
session_start();
$db = new SQLite3('quran_app.db');

// Create tables if not exists
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT)');
$db->exec('CREATE TABLE IF NOT EXISTS albums (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, name TEXT, FOREIGN KEY(user_id) REFERENCES users(id))');
$db->exec('CREATE TABLE IF NOT EXISTS album_verses (id INTEGER PRIMARY KEY AUTOINCREMENT, album_id INTEGER, verse_surah INTEGER, verse_ayah INTEGER, FOREIGN KEY(album_id) REFERENCES albums(id))');

function getVerses($message)
{
    $url = "https://nyari.me/api/solusiquran.php?message=" . urlencode($message);
    $response = @file_get_contents($url);
    return $response === false ? null : json_decode($response, true);
}

function getVerseDetails($surah, $ayah)
{
    $url = "https://quran-api-id.vercel.app/surahs/$surah/ayahs/$ayah";
    $response = @file_get_contents($url);
    return $response === false ? null : json_decode($response, true);
}

function getSurahDetails($surah)
{
    $url = "https://quran-api-id.vercel.app/surahs/$surah";
    $response = @file_get_contents($url);
    return $response === false ? null : json_decode($response, true);
}

function getEssence($paragraph)
{
    $url = "https://nyari.me/api/intiquran.php?paragraph=" . urlencode($paragraph);
    $response = @file_get_contents($url);
    return $response === false ? null : json_decode($response, true);
}

function registerUser($username, $password)
{
    global $db;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
    return $stmt->execute();
}

function loginUser($username, $password)
{
    global $db;
    $stmt = $db->prepare('SELECT id, password FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function createAlbum($userId, $albumName)
{
    global $db;
    $stmt = $db->prepare('INSERT INTO albums (user_id, name) VALUES (:user_id, :name)');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':name', $albumName, SQLITE3_TEXT);
    return $stmt->execute();
}

function getUserAlbums($userId)
{
    global $db;
    $stmt = $db->prepare('SELECT * FROM albums WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $albums = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $albums[] = $row;
    }
    return $albums;
}

function addToAlbum($albumId, $surah, $ayah)
{
    global $db;
    $stmt = $db->prepare('INSERT INTO album_verses (album_id, verse_surah, verse_ayah) VALUES (:album_id, :surah, :ayah)');
    $stmt->bindValue(':album_id', $albumId, SQLITE3_INTEGER);
    $stmt->bindValue(':surah', $surah, SQLITE3_INTEGER);
    $stmt->bindValue(':ayah', $ayah, SQLITE3_INTEGER);
    return $stmt->execute();
}

function removeFromAlbum($albumId, $surah, $ayah)
{
    global $db;
    $stmt = $db->prepare('DELETE FROM album_verses WHERE album_id = :album_id AND verse_surah = :surah AND verse_ayah = :ayah');
    $stmt->bindValue(':album_id', $albumId, SQLITE3_INTEGER);
    $stmt->bindValue(':surah', $surah, SQLITE3_INTEGER);
    $stmt->bindValue(':ayah', $ayah, SQLITE3_INTEGER);
    return $stmt->execute();
}

function getAlbumVerses($albumId)
{
    global $db;
    $stmt = $db->prepare('SELECT * FROM album_verses WHERE album_id = :album_id');
    $stmt->bindValue(':album_id', $albumId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $verses = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $verses[] = $row;
    }
    return $verses;
}

if (!isset($_SESSION['search_results'])) {
    $_SESSION['search_results'] = [
        'verses' => [],
        'essenceResult' => null,
    ];
}

$verses = $_SESSION['search_results']['verses'];
$essenceResult = $_SESSION['search_results']['essenceResult'];
$message = '';

function renameAlbum($albumId, $newName)
{
    global $db;
    $stmt = $db->prepare('UPDATE albums SET name = :name WHERE id = :id AND user_id = :user_id');
    $stmt->bindValue(':name', $newName, SQLITE3_TEXT);
    $stmt->bindValue(':id', $albumId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    return $stmt->execute();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                if (registerUser($_POST['username'], $_POST['password'])) {
                    $message = "Registrasi berhasil. Silakan login.";
                } else {
                    $message = "Registrasi gagal. Username mungkin sudah digunakan.";
                }
                break;
            case 'login':
                if (loginUser($_POST['username'], $_POST['password'])) {
                    $message = "Login berhasil.";
                } else {
                    $message = "Login gagal. Periksa username dan password Anda.";
                }
                break;
            case 'logout':
                session_destroy();
                $message = "Anda telah keluar.";
                break;
            case 'search':
                $result = getVerses($_POST['message']);
                if ($result && $result['success'] && !empty($result['data']['verses'])) {
                    $allTranslations = [];
                    $verses = []; // Reset verses for new search
                    foreach ($result['data']['verses'] as $verse) {
                        $verseDetails = getVerseDetails($verse['surah'], $verse['ayah']);
                        if ($verseDetails) {
                            $surahDetails = getSurahDetails($verse['surah']);
                            $surahName = $surahDetails['name'] ?? 'Nama Surah tidak tersedia';
                            $verses[] = [
                                'surah' => $verse['surah'],
                                'ayah' => $verse['ayah'],
                                'arab' => $verseDetails['arab'] ?? '',
                                'translation' => $verseDetails['translation'] ?? '',
                                'audio' => $verseDetails['audio']['alafasy'] ?? '',
                                'tafsir' => $verseDetails['tafsir']['jalalayn'] ?? 'Tafsir tidak tersedia',
                                'surah_name' => $surahName,
                            ];
                            $allTranslations[] = $verseDetails['translation'] ?? '';
                        }
                    }
                    $combinedTranslations = implode(' ', $allTranslations);
                    $essenceResult = getEssence($combinedTranslations . ' berdasarkan seluruh ayat tersebut, apa pesan untuk: ' . $_POST['message']);

                    // Store search results in session
                    $_SESSION['search_results'] = [
                        'verses' => $verses,
                        'essenceResult' => $essenceResult,
                    ];
                }
                break;

            case 'clear_search':
                // Clear search results from session
                $_SESSION['search_results'] = [
                    'verses' => [],
                    'essenceResult' => null,
                ];
                $verses = [];
                $essenceResult = null;
                break;
            case 'create_album':
                if (isset($_SESSION['user_id'])) {
                    if (createAlbum($_SESSION['user_id'], $_POST['album_name'])) {
                        $message = "Album berhasil dibuat.";
                    } else {
                        $message = "Gagal membuat album.";
                    }
                } else {
                    $message = "Silakan login terlebih dahulu.";
                }
                break;
            case 'add_to_album':
                if (isset($_SESSION['user_id'])) {
                    if (addToAlbum($_POST['album_id'], $_POST['surah'], $_POST['ayah'])) {
                        $message = "Ayat berhasil ditambahkan ke album.";
                    } else {
                        $message = "Gagal menambahkan ayat ke album.";
                    }
                } else {
                    $message = "Silakan login terlebih dahulu.";
                }
                break;
            case 'remove_from_album':
                if (isset($_SESSION['user_id'])) {
                    if (removeFromAlbum($_POST['album_id'], $_POST['surah'], $_POST['ayah'])) {
                        $message = "Ayat berhasil dihapus dari album.";
                    } else {
                        $message = "Gagal menghapus ayat dari album.";
                    }
                } else {
                    $message = "Silakan login terlebih dahulu.";
                }
                break;
            case 'rename_album':
                if (isset($_SESSION['user_id'])) {
                    if (renameAlbum($_POST['album_id'], $_POST['new_name'])) {
                        $message = "Album berhasil diubah namanya.";
                    } else {
                        $message = "Gagal mengubah nama album.";
                    }
                } else {
                    $message = "Silakan login terlebih dahulu.";
                }
                break;
        }
    }
}

$userAlbums = isset($_SESSION['user_id']) ? getUserAlbums($_SESSION['user_id']) : [];
?>