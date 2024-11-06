<?php include 'functions.php'; ?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teman Al-Quran</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Memproses permintaan Anda...</div>
        </div>
        
        <div class="main-container">
            <header class="app-header">
                <h1 class="app-title">Teman Al-Quran</h1>
                <p class="app-subtitle">Temukan kedamaian dalam ayat-ayat suci Al-Quran</p>
            </header>
            
            <div class="search-container">
                    <form id="searchForm" method="post">
                        <input type="hidden" name="action" value="search">
                    <div class="mb-3">
                        <label for="message" class="form-label">Ceritakan masalahmu atau sampaikan pertanyaanmu!</label>
                        <textarea class="form-control" id="message" name="message" placeholder="Contoh: ketenangan hati, kesabaran, saya merasa..." rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Cari Petunjuk
                    </button>
                </form>
            </div>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="auth-container">
                    <div class="auth-card">
                        <h2>Daftar</h2>
                        <form method="post">
                            <input type="hidden" name="action" value="register">
                            <div class="auth-form-group">
                                <input type="text" name="username" placeholder="Username" required>
                            </div>
                            <div class="auth-form-group">
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>
                    </div>
                    
                    <div class="auth-card">
                        <h2>Masuk</h2>
                        <form method="post">
                            <input type="hidden" name="action" value="login">
                            <div class="auth-form-group">
                                <input type="text" name="username" placeholder="Username" required>
                            </div>
                            <div class="auth-form-group">
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Masuk</button>
                        </form>
                    </div>
                </div>
                <?php endif;?>
            
            <?php if (!empty($verses)): ?>
                <div class="d-flex justify-content-end mb-4">
                    <form method="post">
                        <input type="hidden" name="action" value="clear_search">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-times me-2"></i>Bersihkan Pencarian
                        </button>
                    </form>
                </div>
                <?php endif;?>
                
                <div id="searchResults">
                    <?php if (isset($verses) && !empty($verses)): ?>
                    <?php if (isset($essenceResult) && $essenceResult['success']): ?>
                    <div class="verse-card">
                        <div class="verse-header">
                            <h3 class="mb-0">Intisari Ayat-ayat</h3>
                        </div>
                        <div class="verse-content">
                            <div class="essence-text"><?=$essenceResult['data']['essence']?></div>
                        </div>
                    </div>
                    <?php endif;?>
                        <?php foreach ($verses as $index => $verse): ?>
                            <div class="verse-card">
                                <div class="verse-header">
                                    <h3 class="mb-0">Surah <?=$verse['surah_name']?> (<?=$verse['surah']?>), Ayat <?=$verse['ayah']?></h3>
                                </div>
                                <div class="verse-content">
                                    <div class="arabic-text"><?=$verse['arab']?></div>
                                    <div class="translation-text"><?=$verse['translation']?></div>
                                    <div class="verse-actions">
                                        <?php if (!empty($verse['audio'])): ?>
                                            <button class="btn btn-icon play-audio" data-audio-url="<?=$verse['audio']?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif;?>
                                        
                                        <button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#tafsirModal<?=$index?>">
                                            <i class="fas fa-book-open"></i>
                                        </button>
                                        
                                        <button class="btn btn-icon" onclick="window.location.href='https://www.nyari.me/quran/baca.php?surah=<?=$verse['surah']?>&verse=<?=$verse['ayah']?>'">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                        
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#addToAlbumModal<?=$index?>">
                                                <i class="fas fa-bookmark"></i>
                                            </button>
                                        <?php endif;?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tafsir Modal -->
                            <div class="modal fade" id="tafsirModal<?=$index?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Tafsir Surah <?=$verse['surah_name']?> (<?=$verse['surah']?>), Ayat <?=$verse['ayah']?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="tafsir-content">
                                        <?=$verse['tafsir']?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add to Album Modal -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="modal fade" id="addToAlbumModal<?=$index?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tambahkan ke Album</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="post">
                                            <input type="hidden" name="action" value="add_to_album">
                                            <input type="hidden" name="surah" value="<?=$verse['surah']?>">
                                            <input type="hidden" name="ayah" value="<?=$verse['ayah']?>">
                                            <div class="mb-3">
                                                <select name="album_id" class="form-select" required>
                                                    <option value="">Pilih Album</option>
                                                    <?php foreach ($userAlbums as $album): ?>
                                                        <option value="<?=$album['id']?>"><?=htmlspecialchars($album['name'])?></option>
                                                        <?php endforeach;?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">Tambahkan ke Album</button>
                                            </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif;?>
                        <?php endforeach;?>
                    <?php endif;?>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="albums-container">
                        <div class="d-flex justify-content-between align-items-center mb-4 w-100">
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="action" value="create_album">
                                <input type="text" class="form-control" name="album_name" placeholder="Nama Album" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                </button>
                            </form>
                        </div>
                        
                        <?php if (!empty($userAlbums)): ?>
                            <?php foreach ($userAlbums as $album): ?>
                                <div class="album-card">
                                    <div class="album-header">
                                        <h3 class="album-title" id="album-name-<?=$album['id']?>"><?=htmlspecialchars($album['name'])?></h3>
                                        <div class="album-actions">
                                            <button class="btn btn-icon" onclick="toggleRenameForm(<?=$album['id']?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-icon" onclick="toggleVerses(<?=$album['id']?>)">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <form id="rename-form-<?=$album['id']?>" style="display: none;" onsubmit="renameAlbum(event, <?=$album['id']?>)" class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Nama album baru" required>
                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                </div>
                            </form>
                            
                            <div id="verses-<?=$album['id']?>" style="display: none;">
                                <?php
                                    $albumVerses = getAlbumVerses($album['id']);
                                    foreach ($albumVerses as $savedVerse):
                                        $verseDetails = getVerseDetails($savedVerse['verse_surah'], $savedVerse['verse_ayah']);
                                        $surahDetails = getSurahDetails($savedVerse['verse_surah']);
                                        ?>
		                                    <div class="verse-card">
                                                <div class="verse-header">
                                                    <h4 class="mb-0">
	                                                    <a href="#" class="text-white text-decoration-none" onclick="toggleVerseContent(event, <?=$album['id']?>, <?=$savedVerse['verse_surah']?>, <?=$savedVerse['verse_ayah']?>)">
                                                            Surah <?=$surahDetails['name'] ?? 'Tidak tersedia'?> (<?=$savedVerse['verse_surah']?>), Ayat <?=$savedVerse['verse_ayah']?>
		                                                </a>
		                                            </h4>
		                                        </div>
		                                        <div id="verse-content-<?=$album['id']?>-<?=$savedVerse['verse_surah']?>-<?=$savedVerse['verse_ayah']?>" class="verse-content" style="display: none;">
                                                    <div class="arabic-text"><?=$verseDetails['arab'] ?? ''?></div>
		                                            <div class="translation-text"><?=$verseDetails['translation'] ?? ''?></div>
                                                    
		                                            <div class="verse-actions">
                                                        <?php if (!empty($verseDetails['audio']['alafasy'])): ?>
		                                                    <button class="btn btn-icon play-audio" data-audio-url="<?=$verseDetails['audio']['alafasy']?>">
                                                                <i class="fas fa-play"></i>
		                                                    </button>
	                                                        <?php endif;?>
                                                            
                                                            <button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#tafsirModalAlbum<?=$album['id']?>_<?=$savedVerse['verse_surah']?>_<?=$savedVerse['verse_ayah']?>">
                                                                <i class="fas fa-book-open"></i>
                                                            </button>
                                                            
                                                            <form method="post" class="d-inline">
                                                            <input type="hidden" name="action" value="remove_from_album">
                                                            <input type="hidden" name="album_id" value="<?=$album['id']?>">
                                                            <input type="hidden" name="surah" value="<?=$savedVerse['verse_surah']?>">
                                                            <input type="hidden" name="ayah" value="<?=$savedVerse['verse_ayah']?>">
                                                            <button type="submit" class="btn btn-icon btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                    </div>

                                    <!-- Tafsir Modal for Album Verses -->
                                    <div class="modal fade" id="tafsirModalAlbum<?=$album['id']?>_<?=$savedVerse['verse_surah']?>_<?=$savedVerse['verse_ayah']?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tafsir Surah <?=$surahDetails['name'] ?? 'Tidak tersedia'?> (<?=$savedVerse['verse_surah']?>), Ayat <?=$savedVerse['verse_ayah']?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="tafsir-content">
                                                        <?=$verseDetails['tafsir']['jalalayn'] ?? 'Tafsir tidak tersedia'?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach;?>
                                </div>
                        </div>
                        <?php endforeach;?>
                <?php endif;?>
            </div>
            <?php endif;?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="scripts.js"></script>
</body>
</html>
