document.addEventListener('DOMContentLoaded', function() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    
    let currentAudio = null;
    
    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }
    
    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }
    
    
    function initAudioButtons() {
        document.querySelectorAll('.play-audio').forEach(button => {
            button.addEventListener('click', function() {
                const audioUrl = this.getAttribute('data-audio-url');
                if (currentAudio) {
                    currentAudio.pause();
                    if (currentAudio.src === audioUrl) {
                        currentAudio = null;
                        this.innerHTML = '<i class="fas fa-play"></i>';
                        return;
                    }
                }
                currentAudio = new Audio(audioUrl);
                currentAudio.play();
                this.innerHTML = '<i class="fas fa-stop"></i>';
                currentAudio.onended = () => {
                    this.innerHTML = '<i class="fas fa-play"></i>';
                    currentAudio = null;
                };
            });
        });
    }

    initAudioButtons();

    // Modify your existing AJAX call
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#message').val()) {
            alert('Silakan masukkan pesan Anda.');
            return;
        }

        showLoading();

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response;
                const newResults = tempDiv.querySelector('#searchResults').innerHTML;
                $('#searchResults').html(newResults);
                initAudioButtons();
                
                $('html, body').animate({
                    scrollTop: $("#searchResults").offset().top
                }, 1000);
                
                const clearSearchButton = tempDiv.querySelector('form[action="clear_search"]');
                if (clearSearchButton) {
                    $('form[action="clear_search"]').remove();
                    $('#searchForm').after(clearSearchButton);
                }
                
                hideLoading();
            },
            error: function() {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                hideLoading();
            }
        });
    });
    
    
    window.toggleRenameForm = function(albumId) {
        const form = document.getElementById(`rename-form-${albumId}`);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    
    window.renameAlbum = function(event, albumId) {
        event.preventDefault();
        const newName = event.target.querySelector('input').value;
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'rename_album',
                album_id: albumId,
                new_name: newName
            },
            success: function(response) {
                document.getElementById(`album-name-${albumId}`).textContent = newName;
                toggleRenameForm(albumId);
            },
            error: function() {
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });
    }
    
    window.toggleVerses = function(albumId) {
        const verses = document.getElementById(`verses-${albumId}`);
        verses.style.display = verses.style.display === 'none' ? 'block' : 'none';
    }
    
    window.toggleVerseContent = function(event, albumId, surah, ayah) {
        event.preventDefault(); // Prevent default anchor behavior
        const content = document.getElementById(`verse-content-${albumId}-${surah}-${ayah}`);
        content.style.display = content.style.display === 'none' ? 'block' : 'none';
    }
});