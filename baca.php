<?php
// baca.php
// Configuration
define('CACHE_DIRECTORY', __DIR__ . '/cache');
define('CACHE_DURATION', 86400); // 24 hours in seconds
define('API_BASE_URL', 'https://quran-api-id.vercel.app');
// Initialize cache directory
if (!file_exists(CACHE_DIRECTORY)) {
    mkdir(CACHE_DIRECTORY, 0777, true);
}

class QuranAPI {
    private $cache;
    
    public function __construct() {
        $this->cache = new Cache();
    }
    
    private function fetchFromAPI($endpoint) {
        $url = API_BASE_URL . $endpoint;
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => 'Accept: application/json'
                ]
            ]);
        
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Failed to fetch data from API');
            }
            
            return json_decode($response, true);
        }
        
        public function getSurahs() {
            $cacheKey = 'surahs_list';
        
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== false) {
                return $cachedData;
            }
            
            $surahs = $this->fetchFromAPI('/surahs');
            $this->cache->set($cacheKey, $surahs);
            
            return $surahs;
        }
        
        public function getSurah($surahNumber) {
            if (!is_numeric($surahNumber) || $surahNumber < 1 || $surahNumber > 114) {
                throw new InvalidArgumentException('Invalid surah number');
            }
            
        $cacheKey = "surah_{$surahNumber}";
        
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== false) {
            return $cachedData;
        }
        
        $surah = $this->fetchFromAPI("/surahs/{$surahNumber}");
        $this->cache->set($cacheKey, $surah);
        
        return $surah;
    }
    
    public function getVerses($surahNumber) {
        if (!is_numeric($surahNumber) || $surahNumber < 1 || $surahNumber > 114) {
            throw new InvalidArgumentException('Invalid surah number');
        }
        
        $cacheKey = "verses_{$surahNumber}";
        
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== false) {
            return $cachedData;
        }
        
        $verses = $this->fetchFromAPI("/surahs/{$surahNumber}/ayahs");
        $this->cache->set($cacheKey, $verses);
        
        return $verses;
    }
}

class Cache {
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        
        if (!isset($data['expiry']) || !isset($data['content'])) {
            return false;
        }
        
        if (time() > $data['expiry']) {
            @unlink($filename);
            return false;
        }
        
        return $data['content'];
    }
    
    public function set($key, $content) {
        $filename = $this->getCacheFilename($key);
        $data = [
            'expiry' => time() + CACHE_DURATION,
            'content' => $content
        ];
        
        return file_put_contents($filename, json_encode($data));
    }
    
    private function getCacheFilename($key) {
        return CACHE_DIRECTORY . '/' . md5($key) . '.cache';
    }
}

function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Initialize the application state
try {
    $quranAPI = new QuranAPI();
    $error = null;
    $surahs = [];
    $selectedSurah = null;
    $verses = [];
    $loading = false;
    $selectedVerse = null;
    
    // Fetch all surahs
    try {
        $surahs = $quranAPI->getSurahs();
    } catch (Exception $e) {
        $error = "Gagal memuat daftar surah: " . $e->getMessage();
    }
    
    // Handle surah and verse selection
    if (isset($_GET['surah']) && empty($error)) {
        try {
            $surahNumber = filter_input(INPUT_GET, 'surah', FILTER_VALIDATE_INT);
            $verseNumber = filter_input(INPUT_GET, 'verse', FILTER_VALIDATE_INT);
            
            if ($surahNumber === false || $surahNumber === null) {
                throw new InvalidArgumentException('Nomor surah tidak valid');
            }
            
            $selectedSurah = $quranAPI->getSurah($surahNumber);
            $verses = $quranAPI->getVerses($surahNumber);
            
            if ($verseNumber !== null && $verseNumber > 0) {
                $selectedVerse = $verseNumber;
            }
            
        } catch (Exception $e) {
            $error = "Gagal memuat surah: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $error = "Terjadi kesalahan sistem: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teman Al-Quran</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="main-container">
            <header class="app-header">
                <h1 class="app-title">Teman Al-Quran</h1>
                <p class="app-subtitle">Baca dan dengarkan Al-Quran dengan terjemahan bahasa Indonesia</p>
            </header>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>
                <?php endif; ?>
                
                
                <form class="" id="navigationForm" onsubmit="return navigateToVerse(event)">
                    
                    <div class="auth-form-group mb-3 ">
                        <select id="surahSelect" name="surah" class="form-select" required>
                            <option value="" disabled <?= empty($_GET['surah']) ? 'selected' : '' ?>>Pilih Surah</option>
                            <?php foreach ($surahs as $surah): ?>
                                <option value="<?= h($surah['number']) ?>" 
                                <?= isset($_GET['surah']) && $_GET['surah'] == $surah['number'] ? 'selected' : '' ?>
                                data-verses="<?= h($surah['numberOfVerses']) ?>">
                                <?= h($surah['number']) ?>. <?= h($surah['name']) ?> - <?= h($surah['translation']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="auth-form-group mb-3 ">
                        <input type="number" id="verseInput" name="verse" class="form-select" min="1" value="<?= h($selectedVerse ?? '') ?>" placeholder="Ayat">
                    </div>
                        <button type="submit" class="btn btn-primary mb-3 w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                    
                    
                   <?php if ($selectedSurah): ?>
    <div class="surah-info">
        <h2><?= h($selectedSurah['name']) ?> - <?= h($selectedSurah['translation']) ?></h2>
        <p><?= h($selectedSurah['description']) ?></p>
    </div>

    <?php foreach ($verses as $verse): ?>
    <div class="verse-card" id="verse-<?= $verse['number']['inSurah'] ?>">
        <div class="verse-header">
            <span class="verse-number">Ayat <?= h($verse['number']['inSurah']) ?></span>
            
        </div>
            
           
                    <div class="verse-content">
                        <p class="arabic-text"><?= $verse['arab'] ?></p>
                        <p class="translation-text"><?= h($verse['translation']) ?></p>
                        <?php if (!empty($verse['audio'])): ?>
                            <div class="audio-controls">
                                <select class="audio-select" onchange="changeReciter(<?= $verse['number']['inSurah'] ?>, this.value)">
                                    <?php if (!empty($verse['audio'])): ?>
            <?php foreach ($verse['audio'] as $reciter => $audioUrl): ?>
                <option value="<?= h($audioUrl) ?>" <?= ($reciter === key($verse['audio'])) ? 'selected' : '' ?>>
                    <?= h(ucfirst($reciter)) ?>
                </option>
                <?php endforeach; ?>
                <?php else: ?>
                    <option disabled>Tidak ada reciter tersedia</option>
                    <?php endif; ?>
                </select>
                <button class="audio-button" type="button" data-verse="<?= $verse['number']['inSurah'] ?>"onclick="toggleAudio(this, this.parentElement.querySelector('.audio-select').value)">
                <i class="bi bi-play-fill"></i>
            </button>
            <button class="audio-button" data-bs-toggle="modal" data-bs-target="#tafsirModal_<?=$selectedSurah['number']?>_<?=$verse['number']['inSurah']?>">
                <i class="bi bi-book"></i>
            </button>
        </div>
        
        <?php endif; ?>
    </div>
</div>
<div class="modal fade" id="tafsirModal_<?=$selectedSurah['number']?>_<?=$verse['number']['inSurah']?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tafsir Surah <?= h($selectedSurah['name']) ?> (<?=$selectedSurah['number']?>), Ayat <?=$verse['number']['inSurah']?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="tafsir-content">
                        <div class="tafsir-text">
                            <?= h($verse['tafsir']['jalalayn']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle tafsir modal events
    document.querySelectorAll('.modal').forEach(modalElement => {
        modalElement.addEventListener('show.bs.modal', async function(event) {
            const modal = this;
            const modalId = modal.id;
            const [_, surahNumber, verseNumber] = modalId.split('_');
        });
    });
});
</script>

<script>
    function navigateToVerse(event) {
        event.preventDefault();
        
        const surahSelect = document.getElementById('surahSelect');
        const verseInput = document.getElementById('verseInput');
        const selectedOption = surahSelect.selectedOptions[0];
        
        if (!surahSelect.value) {
            alert('Silakan pilih surah terlebih dahulu');
            return false;
        }
        
        let url = `?surah=${surahSelect.value}`;
        
        if (verseInput.value) {
            const maxVerses = parseInt(selectedOption.dataset.verses);
            const verseNumber = parseInt(verseInput.value);
            
            if (verseNumber < 1 || verseNumber > maxVerses) {
                alert(`Nomor ayat harus antara 1 dan ${maxVerses} untuk surah ini`);
                return false;
            }
            
            url += `&verse=${verseInput.value}`;
        }
        
        window.location.href = url;
        return false;
    }
    
    // Update verse input max value when surah changes
    document.getElementById('surahSelect').addEventListener('change', function() {
        const verseInput = document.getElementById('verseInput');
        const selectedOption = this.selectedOptions[0];
        
        if (selectedOption && selectedOption.dataset.verses) {
            verseInput.max = selectedOption.dataset.verses;
            }
        });
        
        let currentAudio = null;
        let currentButton = null;
        const audioElements = new Map();
        
        function createAudioElement(url) {
            const audio = new Audio(url);
            audio.addEventListener('ended', () => {
                if (currentButton) {
                    currentButton.innerHTML = '<i class="bi bi-play-fill"></i>';
                    currentButton = null;
                }
                currentAudio = null;
            });
            return audio;
        }
        
        function toggleAudio(button, audioUrl) {
            // Stop current audio if playing
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                if (currentButton) {
                    currentButton.innerHTML = '<i class="bi bi-play-fill"></i>';
                }
            }
            
            // If clicking same button, just stop
            if (currentButton === button) {
                currentButton = null;
                currentAudio = null;
                return;
            }
            
            // Get or create audio element
            let audio = audioElements.get(audioUrl);
            if (!audio) {
                audio = createAudioElement(audioUrl);
                audioElements.set(audioUrl, audio);
            }
            
            // Play new audio
            audio.play().then(() => {
                button.innerHTML = '<i class="bi bi-pause-fill"></i>';
                currentAudio = audio;
                currentButton = button;
            }).catch(err => {
                console.error('Error playing audio:', err);
                alert('Gagal memutar audio. Silakan coba lagi.');
            });
        }
        
        function changeReciter(verseNumber, audioUrl) {
            const button = document.querySelector(`button[data-verse="${verseNumber}"]`);
            if (currentButton === button) {
                toggleAudio(button, audioUrl);
            }
        }
        
        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            const surahSelect = document.querySelector('select[name="surah"]');
            
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
                
                if (surahSelect && surahSelect.value) {
                    const currentSurah = parseInt(surahSelect.value);
                    let newSurah;
                    
                    if (e.key === 'ArrowUp' && currentSurah > 1) {
                        newSurah = currentSurah - 1;
                    } else if (e.key === 'ArrowDown' && currentSurah < 114) {
                        newSurah = currentSurah + 1;
                    }
                    
                    if (newSurah) {
                        window.location.href = '?surah=' + newSurah;
                    }
                }
            }
        });
        
        // Handle Touch Navigation
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const surahSelect = document.querySelector('select[name="surah"]');
            const swipeThreshold = 50;
            
            if (surahSelect && surahSelect.value) {
                const currentSurah = parseInt(surahSelect.value);
                let newSurah;
                
                if (touchEndX < touchStartX - swipeThreshold && currentSurah < 114) {
                    // Swipe left - next surah
                    newSurah = currentSurah + 1;
                } else if (touchEndX > touchStartX + swipeThreshold && currentSurah > 1) {
                    // Swipe right - previous surah
                    newSurah = currentSurah - 1;
                }
                
                if (newSurah) {
                    window.location.href = '?surah=' + newSurah;
                }
            }
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            audioElements.forEach(audio => {
                if (!audio.paused) {
                    audio.pause();
                }
            });
            audioElements.clear();
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const verse = urlParams.get('verse');
            if (verse) {
                const verseElement = document.getElementById(`verse-${verse}`);
                if (verseElement) {
                    verseElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    verseElement.classList.add('highlighted');
                }
            }
        });
        
        // Update verse input max on page load
        window.addEventListener('load', () => {
            const surahSelect = document.getElementById('surahSelect');
            const selectedOption = surahSelect.selectedOptions[0];
            const verseInput = document.getElementById('verseInput');
            
            if (selectedOption && selectedOption.dataset.verses) {
                verseInput.max = selectedOption.dataset.verses;
            }
        });
        </script>
</body>
</html>