async function toggleLike(type, id, button) {
    try {
        const response = await fetch('/blog/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                id: id
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const counter = button.querySelector('.like-count');
            const icon = button.querySelector('.like-icon');
            
            if (counter) {
                counter.textContent = result.count;
            }
            
            if (result.liked) {
                button.classList.add('liked');
                if (icon) icon.textContent = '❤️';
            } else {
                button.classList.remove('liked');
                if (icon) icon.textContent = '🤍';
            }
        } else if (result.error === 'Не авторизован') {
            window.location.href = '/blog/login.php';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

