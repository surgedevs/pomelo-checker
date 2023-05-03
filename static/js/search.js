document.getElementById('search').addEventListener('submit', function(event) {
    event.preventDefault();
    
    let username = document.getElementById('username').value;
    window.location.href = '/' + encodeURIComponent(username);
});